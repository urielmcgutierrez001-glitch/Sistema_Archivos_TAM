#!/usr/bin/env python3
import pymysql

DB_CONFIG = {
    'host': 'bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com',
    'user': 'uh5uxh0yxbs9cxva',
    'password': 'HdTIK6C8X5M5qsQUTXoE',
    'database': 'bf7yz05jw1xmnb2vukrs',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

sql_eliminar_indices = [
    "DROP INDEX uk_diario ON registro_diario",
    "DROP INDEX uk_validacion ON registro_diario",
    "DROP INDEX uk_diario ON documentos", # Intentar en ambas tablas por si acaso
    "DROP INDEX uk_validacion ON documentos"
]

try:
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cursor:
        print("Eliminando restricciones únicas en Clever Cloud...")
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
