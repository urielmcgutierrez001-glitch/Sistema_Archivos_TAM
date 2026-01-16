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
        print("Eliminando restriccion uk_contenedor_tipo_doc...")
        sql = "ALTER TABLE contenedores_fisicos DROP INDEX uk_contenedor_tipo_doc"
        cursor.execute(sql)
        print("✅ Restriccion eliminada.")
    conn.close()
except Exception as e:
    print(f"Error (puede que ya no exista): {e}")
