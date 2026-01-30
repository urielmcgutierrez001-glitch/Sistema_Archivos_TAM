
import pymysql

# Database Configuration
config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'cursorclass': pymysql.cursors.DictCursor
}

def list_tables(connection):
    print("\n--- Listing Tables ---")
    with connection.cursor() as cursor:
        cursor.execute("SHOW TABLES")
        tables = [list(row.values())[0] for row in cursor.fetchall()]
        for table in tables:
            print(f"  - {table}")

try:
    conn = pymysql.connect(**config)
    list_tables(conn)
    conn.close()
except Exception as e:
    print(f"Error: {e}")
