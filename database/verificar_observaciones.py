import pymysql

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
        print("=== MUESTRA DE OBSERVACIONES ===")
        cursor.execute("SELECT DISTINCT observaciones FROM documentos WHERE observaciones IS NOT NULL AND LENGTH(observaciones) > 3 LIMIT 10")
        for row in cursor.fetchall():
            print(f"- {row['observaciones']}")
            
        print("\n=== MUESTRA DE ESTADOS ===")
        cursor.execute("SELECT estado_documento, COUNT(*) as c FROM documentos GROUP BY estado_documento")
        for row in cursor.fetchall():
            print(f"- {row['estado_documento']}: {row['c']}")

    conn.close()
except Exception as e:
    print(e)
