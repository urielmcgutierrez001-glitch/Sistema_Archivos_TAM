
import pymysql

# Configuración de BD
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
        cursor.execute("SELECT id, nombre FROM ubicaciones")
        ubicaciones = cursor.fetchall()
        print("--- UBICACIONES EN BD ---")
        for u in ubicaciones:
            print(f"ID: {u['id']}, Nombre: {u['nombre']}")
    conn.close()
except Exception as e:
    print(f"Error: {e}")
