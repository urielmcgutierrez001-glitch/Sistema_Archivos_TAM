import pymysql
import os

# Configuración
HOST = 'localhost'
USER = 'root'
PASSWORD = '' # Ajustar si es necesario
DB = 'tamep_archivos'

def run():
    print(f"Conectando a {DB} en {HOST}...")
    try:
        connection = pymysql.connect(host=HOST, user=USER, password=PASSWORD, database=DB)
        cursor = connection.cursor()
        print("Conectado.")
        
        # Leer SQL
        with open('database/migration_normalization.sql', 'r') as f:
            sql_script = f.read()
            
        statements = sql_script.split(';')
        
        for statement in statements:
            if statement.strip():
                print(f"Ejecutando SQL: {statement[:50]}...")
                try:
                    cursor.execute(statement)
                    print("OK.")
                except Exception as e:
                    print(f"Error o advertencia (posiblemente ya existe columna): {e}")
        
        connection.commit()
        print("Migración completada.")
        
    except Exception as e:
        print(f"Error fatal: {e}")
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

if __name__ == '__main__':
    run()
