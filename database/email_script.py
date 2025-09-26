import time
import pyodbc
import smtplib
import requests
import os
import re
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders
from urllib.parse import unquote, parse_qs, quote

DB_HOST = 'db_host'
DB_NAME = 'db_name'
DB_USER = 'db_user'
DB_PASSWORD = 'db_password'

SMTP_SERVER = 'server_ip_or_hostname'
SMTP_PORT = 25

TABLE_NAME = "table_name"

def connect_to_db():
    conn_str = f'DRIVER={{SQL Server}};SERVER={DB_HOST};DATABASE={DB_NAME};UID={DB_USER};PWD={DB_PASSWORD}'
    return pyodbc.connect(conn_str)

def fix_attachment_url(attachment_url):
    if "https://server_ip_or_hostname:8080" in attachment_url:
        attachment_url = attachment_url.replace("server_ip_or_hostname:8080", r"\\server_ip_or_hostname\folder")
        #print(f"Attachment Replaced: {attachment_url}")
        
        attachment_url = attachment_url.replace("/", "\\")
        #print(f"Attachment string replaced: {attachment_url}")
        return attachment_url
        
    if attachment_url.startswith(('http://', 'https://')):
        return attachment_url  # Return as-is

def download_attachment(attachment_url):
    
    if os.path.exists(attachment_url):
        # print(f"Attachment is a local file: {attachment_url}")
        return attachment_url  

    # If it's not a local file, assume it's a URL and try to download it
    try:
        response = requests.get(attachment_url, stream=True)
        response.raise_for_status()

        
        filename = os.path.basename(attachment_url)
        filepath = os.path.join('/tmp', filename)

        # Save the downloaded file
        with open(filepath, 'wb') as file:
            for chunk in response.iter_content(chunk_size=8192):
                file.write(chunk)

        print(f"Attachment downloaded and saved to: {filepath}")
        return filepath 
    except Exception as e:
        print(f"Error downloading attachment: {e}")
        return None

def parse_email_components(mess):
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


def send_email(components, recid, cursor, conn):
    msg = MIMEMultipart()
    msg['From'] = components['from']
    msg['To'] = ', '.join([email.strip() for email in components['to'].split(';') if email.strip()])
    msg['Subject'] = components['subject']
 
    # Handle Cc separately
    if components['cc']:
        cc_emails = [email.strip() for email in components['cc'].split(';') if email.strip()]
        msg['Cc'] = ', '.join(cc_emails)
    else:
        cc_emails = []

    # Bcc
    if components['bcc']:
        bcc_emails = [email.strip() for email in components['bcc'].split(';') if email.strip()]
    else:
        bcc_emails = []

    recipients = msg['To'].split(', ') + cc_emails + bcc_emails  # All recipients for sendmail()
    
    msg.attach(MIMEText(components['msgbody'], 'html'))

    
    if components['attachment']:
        attachment_url = components['attachment']
        
        
        if attachment_url.startswith(('http://', 'https://')):
            local_filepath = download_attachment(attachment_url)
        else:
            local_filepath = attachment_url  

        if local_filepath and os.path.exists(local_filepath):
            try:
                with open(local_filepath, 'rb') as attachment:
                    part = MIMEBase('application', 'octet-stream')
                    part.set_payload(attachment.read())
                    encoders.encode_base64(part)
                    part.add_header('Content-Disposition', f'attachment; filename={os.path.basename(local_filepath)}')
                    msg.attach(part)
            except Exception as e:
                print(f"Error attaching file: {e}")
                # Update status to failed
                cursor.execute(f"UPDATE {TABLE_NAME} SET status = 'failed', error_message = ? WHERE recid = ?", (e, recid))
                conn.commit()
                return

    # Send the email
    try:
        with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
            server.sendmail(components['from'], recipients, msg.as_string())
        print(f"Email sent to {components['to']}")
        
        # Delete the record on success
        cursor.execute(f"DELETE FROM {TABLE_NAME} WHERE recid = ?", recid)
        conn.commit()
    except Exception as e:
        print(f"Error sending email: {e}")
        # Update status to failed
        cursor.execute(f"UPDATE {TABLE_NAME} SET status = 'failed', error_message = ? WHERE recid = ?", (e, recid))
        conn.commit()

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
                components['recid'] = recid

                print(f"Processing record ID: {recid}")
                print(f"To: {components['to']}")
                print(f"Bcc: {components['bcc']}")
                print(f"Cc: {components['cc']}")
                print(f"From: {components['from']}")
                print(f"Subject: {components['subject']}")
                print(f"Message Body: {components['msgbody']}")
                print(f"Attachment: {components['attachment']}")

                # Send the email
                send_email(components, recid, cursor, conn)

                row = cursor.fetchone()

            cursor.close()
            conn.close()

        except Exception as e:
            print(f"Error: {e}")

        # Wait for 15 seconds before checking again
        time.sleep(15)

# Run the script
if __name__ == "__main__":
    main()
