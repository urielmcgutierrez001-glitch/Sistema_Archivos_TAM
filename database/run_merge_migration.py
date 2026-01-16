import pymysql
import os

# Configuración de conexión (Hardcoded para este entorno)
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = ''
DB_NAME = 'tamep_archivos'

def run_migration():
    print(f"Conectando a {DB_HOST}...")
    try:
        connection = pymysql.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME,
            cursorclass=pymysql.cursors.DictCursor
        )
        
        script_path = os.path.join(os.path.dirname(__file__), 'migration_merge_hojas_ruta.sql')
        
        with connection:
            with connection.cursor() as cursor:
                # Leer script SQL
                with open(script_path, 'r', encoding='utf-8') as f:
                    sql_content = f.read()
                
                # Ejecutar por sentencias (separadas por ;)
                statements = sql_content.split(';')
                
                for statement in statements:
                    if statement.strip():
                        print(f"Ejecutando SQL: {statement[:50]}...")
                        cursor.execute(statement)
            
            connection.commit()
            print("Migración completada exitosamente.")
            
    except Exception as e:
        print(f"Error durante la migración: {e}")

if __name__ == "__main__":
    run_migration()
