
import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv

load_dotenv()

def add_column():
    try:
        connection = mysql.connector.connect(
            host=os.getenv('DB_HOST', 'localhost'),
            user=os.getenv('DB_USER', 'root'),
            password=os.getenv('DB_PASS', ''),
            database=os.getenv('DB_NAME', 'sistema_archivos')
        )

        if connection.is_connected():
            cursor = connection.cursor()
            
            # Check if column exists
            check_sql = "SHOW COLUMNS FROM contenedores_fisicos LIKE 'codigo_abc'"
            cursor.execute(check_sql)
            result = cursor.fetchone()
            
            if result:
                print("Column 'codigo_abc' already exists.")
            else:
                print("Adding column 'codigo_abc'...")
                sql = "ALTER TABLE contenedores_fisicos ADD COLUMN codigo_abc VARCHAR(255) NULL AFTER numero"
                cursor.execute(sql)
                connection.commit()
                print("Column added successfully.")

    except Error as e:
        print(f"Error: {e}")
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    add_column()
