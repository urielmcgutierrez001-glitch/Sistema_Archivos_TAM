
import pymysql
import os
import sys

# Configuration (Quick and dirty for this task, referencing database.php would be better but this is faster)
# Based on database.php content seen earlier:
DB_HOST = os.getenv('MYSQL_ADDON_HOST', 'localhost')
DB_PORT = int(os.getenv('MYSQL_ADDON_PORT', 3306))
DB_USER = os.getenv('MYSQL_ADDON_USER', 'root')
DB_PASS = os.getenv('MYSQL_ADDON_PASSWORD', '')
DB_NAME = os.getenv('MYSQL_ADDON_DB', 'tamep_archivos')

MIGRATION_FILE = r'c:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto\database\migration_rename_documentos.sql'

print(f"Connecting to {DB_NAME} at {DB_HOST}:{DB_PORT} as {DB_USER}...")

try:
    connection = pymysql.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        port=DB_PORT,
        cursorclass=pymysql.cursors.DictCursor
    )
    
    print("Connected successfully.")
    
    with connection.cursor() as cursor:
        with open(MIGRATION_FILE, 'r', encoding='utf-8') as f:
            sql_content = f.read()
            
        # Split statements by semicolon, but be careful with stored procedures (not expected here)
        statements = [s.strip() for s in sql_content.split(';') if s.strip()]
        
        for statement in statements:
            if statement.startswith('--'):
                continue
                
            print(f"Executing: {statement[:50]}...")
            try:
                cursor.execute(statement)
                print("OK.")
            except Exception as e:
                print(f"Error executing statement: {e}")
                # Don't exit, might be a partial run or "already exists" error
                
    connection.commit()
    print("Migration finished.")

except Exception as e:
    print(f"Fatal Error: {e}")
finally:
    if 'connection' in locals() and connection.open:
        connection.close()
