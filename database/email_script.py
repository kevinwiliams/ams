import time
import pyodbc
import smtplib
import requests
import os
import re
import logging
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders
from urllib.parse import unquote, parse_qs
from email.utils import parseaddr
from dotenv import load_dotenv

# Load environment variables from .env
load_dotenv()

# ==============================
# Configuration
# ==============================

# Database configuration
DB_HOST = os.getenv("DB_HOST")
DB_NAME = os.getenv("DB_NAME")
DB_USER = os.getenv("DB_USER")
DB_PASSWORD = os.getenv("DB_PASSWORD")
TABLE_NAME = os.getenv("TABLE_NAME", "messagequeue2")  # default fallback

# SMTP configuration
SMTP_SERVER = os.getenv("SMTP_SERVER")
SMTP_PORT = int(os.getenv("SMTP_PORT", 25))
SMTP_USE_TLS = os.getenv("SMTP_USE_TLS", "False").lower() in ("true", "1", "yes")

SITE_URL = os.getenv("SITE_URL")
SHARED_FOLDER_PATH = os.getenv("SHARED_FOLDER_PATH", r"\\172.16.3.78\htdocs\ams")

# Retry settings
MAX_RETRIES = int(os.getenv("MAX_RETRIES", 3))

# Logging setup
logging.basicConfig(
    filename="email_queue.log",
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)

# ==============================
# Utility Functions
# ==============================

def connect_to_db():
    conn_str = f'DRIVER={{SQL Server}};SERVER={DB_HOST};DATABASE={DB_NAME};UID={DB_USER};PWD={DB_PASSWORD}'
    return pyodbc.connect(conn_str)

def is_valid_email(email):
    """Check if an email address is valid."""
    name, addr = parseaddr(email)
    if not addr:
        return False
    regex = r"^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
    return re.match(regex, addr) is not None

def fix_attachment_url(attachment_url):
    """Fix UNC paths for local shared folders, or return as-is if already HTTP/HTTPS."""
    if SITE_URL in attachment_url:
        attachment_url = attachment_url.replace(SITE_URL, SHARED_FOLDER_PATH)
        attachment_url = attachment_url.replace("/", "\\")
        return attachment_url

    if attachment_url.startswith(('http://', 'https://')):
        return attachment_url
    return attachment_url

def download_attachment(attachment_url):
    """Download attachment if remote; return local path if already on disk."""
    if os.path.exists(attachment_url):
        return attachment_url
    try:
        response = requests.get(attachment_url, stream=True, timeout=15)
        response.raise_for_status()
        filename = os.path.basename(attachment_url)
        filepath = os.path.join('/tmp', filename)
        with open(filepath, 'wb') as file:
            for chunk in response.iter_content(chunk_size=8192):
                file.write(chunk)
        logging.info(f"Attachment downloaded to {filepath}")
        return filepath
    except Exception as e:
        logging.error(f"Error downloading attachment: {e}")
        return None

def parse_email_components(mess):
    """Decode and parse email fields from the queue message string."""
    components = {
        "to": "",
        "bcc": "",
        "cc": "",
        "from": "",
        "subject": "",
        "msgbody": "",
        "attachment": ""
    }
    mess = unquote(mess.replace("encoding=UTF-8", ""))
    parsed_data = parse_qs(mess)
    for key in components.keys():
        if key in parsed_data:
            components[key] = parsed_data[key][0]
    if components['attachment']:
        components['attachment'] = fix_attachment_url(components['attachment'])
    return components

# ==============================
# Core Email Sending
# ==============================

def send_email(components, recid, cursor, conn):
    # Validate recipients
    to_emails = [e.strip() for e in components['to'].split(';') if is_valid_email(e.strip())]
    cc_emails = [e.strip() for e in components['cc'].split(';') if is_valid_email(e.strip())]
    bcc_emails = [e.strip() for e in components['bcc'].split(';') if is_valid_email(e.strip())]

    if not (to_emails or cc_emails or bcc_emails):
        logging.warning(f"Record {recid}: No valid recipients")
        cursor.execute(f"UPDATE {TABLE_NAME} SET status = 'failed', error_message = 'No valid recipients' WHERE recid = ?", recid)
        conn.commit()
        return

    msg = MIMEMultipart()
    msg['From'] = components['from']
    msg['To'] = ', '.join(to_emails)
    msg['Subject'] = components['subject']

    if cc_emails:
        msg['Cc'] = ', '.join(cc_emails)

    recipients = to_emails + cc_emails + bcc_emails
    msg.attach(MIMEText(components['msgbody'], 'html'))

    # Handle attachment
    if components['attachment']:
        local_filepath = download_attachment(components['attachment']) if components['attachment'].startswith(('http://', 'https://')) else components['attachment']
        if local_filepath and os.path.exists(local_filepath):
            try:
                with open(local_filepath, 'rb') as attachment:
                    part = MIMEBase('application', 'octet-stream')
                    part.set_payload(attachment.read())
                    encoders.encode_base64(part)
                    part.add_header('Content-Disposition', f'attachment; filename={os.path.basename(local_filepath)}')
                    msg.attach(part)
            except Exception as e:
                logging.error(f"Error attaching file for record {recid}: {e}")
                cursor.execute(f"UPDATE {TABLE_NAME} SET status = 'failed', error_message = ? WHERE recid = ?", (str(e), recid))
                conn.commit()
                return

    # Send email
    try:
        if SMTP_USE_TLS:
            with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
                server.starttls()
                server.sendmail(components['from'], recipients, msg.as_string())
        else:
            with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
                server.sendmail(components['from'], recipients, msg.as_string())

        logging.info(f"Record {recid}: Email sent successfully to {', '.join(recipients)}")
        cursor.execute(f"DELETE FROM {TABLE_NAME} WHERE recid = ?", recid)
        conn.commit()

    except Exception as e:
        logging.error(f"Error sending email for record {recid}: {e}")
        cursor.execute(f"""
            UPDATE {TABLE_NAME}
            SET status = CASE WHEN retry_count + 1 >= ? THEN 'failed' ELSE 'pending' END,
                retry_count = retry_count + 1,
                error_message = ?
            WHERE recid = ?
        """, (MAX_RETRIES, str(e), recid))
        conn.commit()

# ==============================
# Main Loop
# ==============================

def main():
    while True:
        try:
            conn = connect_to_db()
            cursor = conn.cursor()
            cursor.execute(f"SELECT recid, mess FROM {TABLE_NAME} WHERE status = 'pending' ORDER BY recid")
            row = cursor.fetchone()
            while row:
                recid, mess = row
                components = parse_email_components(mess)
                logging.info(f"Processing record ID {recid}")
                send_email(components, recid, cursor, conn)
                row = cursor.fetchone()
            cursor.close()
            conn.close()
        except Exception as e:
            logging.error(f"Main loop error: {e}")
        time.sleep(15)

if __name__ == "__main__":
    main()