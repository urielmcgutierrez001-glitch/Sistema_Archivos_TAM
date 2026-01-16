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

sql_eliminar_indices = [
    "DROP INDEX uk_diario ON documentos",
    "DROP INDEX uk_validacion ON documentos",
    "DROP INDEX idx_tipo_nro ON documentos" # A veces puede dar conflicto si no es unique pero vamos a ver
]

try:
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cursor:
        print("Eliminando restricciones únicas en Localhost...")
        for sql in sql_eliminar_indices:
            try:
                print(f"Ejecutando: {sql}")
                cursor.execute(sql)
                print("✅ Éxito")
            except Exception as e:
                print(f"⚠️  {e}")
    conn.close()
except Exception as e:
    print(f"❌ Error de conexión: {e}")
