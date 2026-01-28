import pymysql
import psycopg2
import sys

# SUPABASE (Source - Postgres)
SUPABASE_URI = "postgresql://postgres.sdovwowdbuzjfwtgnfoa:pasantiatam123@aws-1-us-east-1.pooler.supabase.com:6543/postgres"

# LOCAL (Destination - MySQL)
MYSQL_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4'
}

TABLES_ORDER = [
    'estados',
    'tipos_contenedor',
    'ubicaciones',
    'tipo_documento', # Si aun existe residualmente, aunque la borramos? Mejor solo las vivas.
    'unidades_areas',
    'usuarios',
    'contenedores_fisicos',
    'documentos',
    'prestamos_encabezados',
    'prestamos'
]

TYPE_MAPPING = {
    'integer': 'INT',
    'character varying': 'VARCHAR(255)',
    'text': 'TEXT',
    'smallint': 'TINYINT',
    'timestamp without time zone': 'DATETIME',
    'date': 'DATE',
    'boolean': 'TINYINT(1)'
}

def get_pg_schema(pg_cursor, table):
    pg_cursor.execute(f"""
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = '{table}'
    """)
    return pg_cursor.fetchall()

def migrate():
    try:
        # Connect
        pg_conn = psycopg2.connect(SUPABASE_URI)
        pg_cur = pg_conn.cursor()
        
        my_conn = pymysql.connect(**MYSQL_CONFIG)
        my_cur = my_conn.cursor()
        
        print("INICIANDO MIGRACIÓN SUPABASE -> LOCAL (MySQL)")
        print("---------------------------------------------")

        # Disable FK checks in MySQL
        my_cur.execute("SET FOREIGN_KEY_CHECKS = 0;")
        
        for table in TABLES_ORDER:
            # 1. Get Schema from Postgres
            columns = get_pg_schema(pg_cur, table)
            if not columns:
                print(f"⚠️ Tabla '{table}' no encontrada en Supabase. Saltando.")
                continue

            print(f"📦 Procesando tabla: {table}")
            
            # 2. Recreate Table in MySQL
            my_cur.execute(f"DROP TABLE IF EXISTS `{table}`")
            
            create_query = f"CREATE TABLE `{table}` ("
            col_defs = []
            
            for col_name, dtype in columns:
                mysql_type = TYPE_MAPPING.get(dtype, 'TEXT')
                # Adjust constraints? For simplicity, we make mostly nullable except ID
                if col_name == 'id':
                    col_defs.append(f"`id` INT AUTO_INCREMENT PRIMARY KEY")
                else:
                    col_defs.append(f"`{col_name}` {mysql_type} NULL")
            
            create_query += ", ".join(col_defs) + ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            my_cur.execute(create_query)
            
            # 3. Fetch Data from Postgres
            pg_cur.execute(f'SELECT * FROM "{table}"')
            rows = pg_cur.fetchall()
            
            if not rows:
                print(f"   Insterados: 0 (Tabla vacía)")
                continue

            # 4. Insert into MySQL
            # Get column names dynamically from cursor description to match order
            col_names = [desc[0] for desc in pg_cur.description]
            cols_str = ", ".join([f"`{c}`" for c in col_names])
            placeholders = ", ".join(["%s"] * len(col_names))
            
            insert_query = f"INSERT INTO `{table}` ({cols_str}) VALUES ({placeholders})"
            
            my_cur.executemany(insert_query, rows)
            print(f"   Insertados: {len(rows)}")
            my_conn.commit()

        # Re-enable FK
        my_cur.execute("SET FOREIGN_KEY_CHECKS = 1;")
        print("---------------------------------------------")
        print("✅ Migración a Local completada con éxito.")
        
    except Exception as e:
        print(f"❌ Error crítico: {e}")
    finally:
        pg_conn.close()
        my_conn.close()

if __name__ == "__main__":
    migrate()
