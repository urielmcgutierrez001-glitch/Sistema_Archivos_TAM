import pymysql
import time

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'cursorclass': pymysql.cursors.DictCursor
}

try:
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cursor:
        cursor.execute("SELECT tipo_documento, COUNT(*) as c FROM documentos GROUP BY tipo_documento")
        results = cursor.fetchall()
        print("--- Conteos Actuales ---")
        total = 0
        for r in results:
            print(f"{r['tipo_documento']}: {r['c']}")
            total += r['c']
        print(f"TOTAL: {total}")
    conn.close()
except Exception as e:
    print(e)
