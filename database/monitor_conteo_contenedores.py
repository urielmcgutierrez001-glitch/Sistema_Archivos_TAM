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
        cursor.execute("SELECT COUNT(*) as c FROM contenedores_fisicos")
        res = cursor.fetchone()
        print(f"Contenedores Físicos: {res['c']}")
        
        cursor.execute("SELECT COUNT(*) as c FROM documentos WHERE contenedor_fisico_id IS NOT NULL")
        res2 = cursor.fetchone()
        print(f"Documentos con Contenedor: {res2['c']}")
        
    conn.close()
except Exception as e:
    print(e)
