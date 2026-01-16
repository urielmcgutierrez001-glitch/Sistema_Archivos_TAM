
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def main():
    try:
        conn = pymysql.connect(**DB_CONFIG)
        with conn.cursor() as cursor:
            cursor.execute("DESCRIBE documentos")
            columns = cursor.fetchall()
            print("--- Columns in documentos ---")
            for col in columns:
                print(f"{col['Field']} ({col['Type']})")
    except Exception as e:
        print(f"Error: {e}")
    finally:
        if 'conn' in locals() and conn.open: conn.close()

if __name__ == "__main__":
    main()
