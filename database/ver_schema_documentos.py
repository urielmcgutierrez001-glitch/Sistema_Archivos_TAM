#!/usr/bin/env python3
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

try:
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cursor:
        print("Checking 'documentos' table schema...")
        cursor.execute("SHOW TABLES LIKE 'documentos'")
        if not cursor.fetchone():
            print("Table 'documentos' does not exist. Checking 'registro_diario'...")
            cursor.execute("SHOW TABLES LIKE 'registro_diario'")
            if cursor.fetchone():
                print("Found 'registro_diario'.")
                table = 'registro_diario'
            else:
                print("No main table found!")
                exit()
        else:
            table = 'documentos'
            print("Found 'documentos'.")

        cursor.execute(f"SHOW CREATE TABLE {table}")
        res = cursor.fetchone()
        print("\nCREATE TABLE statement:")
        print(res['Create Table'])
        
        print("\nUnique Constraints/Indexes:")
        cursor.execute(f"SHOW INDEX FROM {table} WHERE Non_unique = 0")
        for idx in cursor.fetchall():
            print(f"- {idx['Key_name']}: {idx['Column_name']}")
    
    conn.close()
except Exception as e:
    print(e)
