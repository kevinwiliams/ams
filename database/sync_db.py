import os
import time
import threading
from datetime import datetime
from sqlalchemy import create_engine, MetaData, Table, select, insert, update, and_, func, text
from sqlalchemy.orm import Session, sessionmaker
from sqlalchemy.exc import SQLAlchemyError
from dotenv import load_dotenv

load_dotenv()

# Configure connection pools properly
SOURCE_DB = os.getenv("SOURCE_DB")
TARGET_DB = os.getenv("TARGET_DB")

# Create engines with connection pooling parameters
source_engine = create_engine(
    SOURCE_DB,
    pool_size=5,
    pool_recycle=3600,  # Recycle connections after 1 hour
    pool_pre_ping=True  # Test connections before use
)
target_engine = create_engine(
    TARGET_DB,
    pool_size=5,
    pool_recycle=3600,
    pool_pre_ping=True
)

# Create session factories WITHOUT passing pool parameters
SourceSession = sessionmaker(bind=source_engine)
TargetSession = sessionmaker(bind=target_engine)

tables = [
    'assignment_list', 'confirmed_logs', 'ob_inventory', 'ob_items',
    'roles', 'sms_messages', 'station_shows', 'transport_log',
    'transport_vehicles', 'users', 'venue_inspections', 'venue_permits'
]

def get_primary_key(table):
    """Identify primary key columns for a table"""
    return [col.name for col in table.columns if col.primary_key]
# One way sync function
def sync_table(table_name):
    """Sync a single table with proper connection handling"""
    source_meta = MetaData()
    target_meta = MetaData()
    
    try:
        # Create new sessions for each table
        with SourceSession() as src_session, TargetSession() as tgt_session:
            try:
                # Reflect table structures
                source_table = Table(table_name, source_meta, autoload_with=source_engine)
                target_table = Table(table_name, target_meta, autoload_with=target_engine)

                # Get source data
                src_data = src_session.execute(select(source_table)).fetchall()
                if not src_data:
                    print(f"[INFO] {table_name}: No data to sync")
                    return

                # Insert data with batch processing
                batch_size = 100
                success_count = 0
                for i in range(0, len(src_data), batch_size):
                    batch = src_data[i:i + batch_size]
                    try:
                        for row in batch:
                            stmt = insert(target_table).values(**row._asdict()).prefix_with("IGNORE")
                            tgt_session.execute(stmt)
                        tgt_session.commit()
                        success_count += len(batch)
                    except SQLAlchemyError as e:
                        tgt_session.rollback()
                        print(f"[ERROR] {table_name}: Batch failed - {str(e)}")
                        continue

                print(f"[SUCCESS] {table_name}: Synced {success_count}/{len(src_data)} records")

            except SQLAlchemyError as e:
                print(f"[ERROR] {table_name}: Table reflection failed - {str(e)}")
                return

    except Exception as e:
        print(f"[CRITICAL] {table_name}: Session creation failed - {str(e)}")

def two_way_sync(table_name):
    """Perform bidirectional synchronization for a table"""
    source_meta = MetaData()
    target_meta = MetaData()
    
    try:
        with SourceSession() as src_session, TargetSession() as tgt_session:
            # Reflect table structures
            source_table = Table(table_name, source_meta, autoload_with=source_engine)
            target_table = Table(table_name, target_meta, autoload_with=target_engine)
            
            # Get primary keys
            src_pk = get_primary_key(source_table)
            tgt_pk = get_primary_key(target_table)
            
            if not src_pk or src_pk != tgt_pk:
                print(f"[WARNING] {table_name}: Primary key mismatch or missing")
                return
            
            # Sync from source to target
            sync_direction(source_table, target_table, src_session, tgt_session, "source_to_target")
            
            # Sync from target to source
            sync_direction(target_table, source_table, tgt_session, src_session, "target_to_source")
            
    except SQLAlchemyError as e:
        print(f"[ERROR] {table_name}: {str(e)}")

def sync_direction(source_table, target_table, source_session, target_session, direction):
    """Sync data in one direction with conflict resolution"""
    table_name = source_table.name
    pk_columns = get_primary_key(source_table)

    # Get source record count
    source_count = source_session.scalar(select(func.count()).select_from(source_table))
    
    # Handle source truncation (0 records)
    if source_count == 0:
        print(f"[WARNING] {table_name} appears truncated in source - purging target")
        target_session.execute(target_table.delete())
        target_session.commit()
        return
    
     # Normal sync with deletion tracking
    source_ids = {row._asdict()[pk_columns[0]] for row in 
                 source_session.execute(select(source_table.c[pk_columns[0]])).fetchall()}
    
    target_ids = {row._asdict()[pk_columns[0]] for row in 
                 target_session.execute(select(target_table.c[pk_columns[0]])).fetchall()}
    
    # Find records deleted from source
    deleted_ids = target_ids - source_ids
    if deleted_ids:
        print(f"[SYNC] Found {len(deleted_ids)} records deleted from source")
        # Create a backup of the target table
        backup_table = f"{table_name}_backup_{datetime.now().strftime('%Y%m%d')}"
        target_session.execute(text(f"CREATE TABLE {backup_table} AS SELECT * FROM {table_name}"))
        print(f"[ALERT] {table_name} has been backed up to {backup_table}")
        # Delete records from target
        target_session.execute(
                target_table.delete().where(
                    target_table.c[pk_columns[0]].in_(deleted_ids)
                )
            )
        target_session.commit()
            
    
    # Get all records from source
    source_data = source_session.execute(select(source_table)).fetchall()
    
    # Get all records from target
    target_records = target_session.execute(select(target_table)).fetchall()
    target_data = {
        tuple(getattr(row, pk) for pk in pk_columns): row
        for row in target_records
    }
    
    inserted = 0
    updated = 0
    conflicts = 0
    
    for src_row in source_data:
        row_dict = {col: getattr(src_row, col) for col in src_row._fields}
        pk_values = tuple(row_dict[pk] for pk in pk_columns)
        
        try:
            if pk_values not in target_data:
                # Insert new record
                stmt = insert(target_table).values(**row_dict)
                target_session.execute(stmt)
                inserted += 1
            else:
                # Update existing record
                where_clause = and_(*[
                    target_table.c[pk] == row_dict[pk] 
                    for pk in pk_columns
                ])
                stmt = update(target_table).where(where_clause).values(**row_dict)
                result = target_session.execute(stmt)
                if result.rowcount > 0:
                    updated += 1
            
            target_session.commit()
            
        except SQLAlchemyError as e:
            target_session.rollback()
            conflicts += 1
            print(f"[CONFLICT] {table_name} {direction}: {str(e)}")
    
    print(f"[SUCCESS] {table_name} {direction}: {inserted} inserted, {updated} updated, {conflicts} conflicts")

def run_sync():
    """Run full two-way sync with timestamp"""
    print(f"\n=== Starting two-way sync at {datetime.now().isoformat()} ===")
    for table in tables:
        print(f"Syncing table: {table}")
        two_way_sync(table)
    print(f"=== Completed sync at {datetime.now().isoformat()} ===\n")


def schedule_sync(interval_minutes=30):
    """Run sync on schedule"""
    while True:
        run_sync()
        time.sleep(interval_minutes * 60)

if __name__ == "__main__":
    try:
        # Initial sync
        run_sync()
        
        # Start scheduled sync in background thread
        scheduler = threading.Thread(target=schedule_sync, daemon=True)
        scheduler.start()
        
        # Keep main thread alive
        while True:
            time.sleep(1)
            
    except KeyboardInterrupt:
        print("\nShutting down gracefully...")
        source_engine.dispose()
        target_engine.dispose()
        print("Connection pools closed.")

# Run: python3 sync_db.py