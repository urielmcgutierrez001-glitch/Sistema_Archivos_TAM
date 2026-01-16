import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos'
}

try:
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cursor:
        cursor.execute("SHOW CREATE TABLE contenedores_fisicos")
        print(cursor.fetchone()[1])
    conn.close()
except Exception as e:
    print(e)
