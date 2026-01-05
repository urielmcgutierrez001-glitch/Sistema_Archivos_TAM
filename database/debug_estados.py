
import pymysql
import sys

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
    cur = conn.cursor()
    
    # 1. Describe table
    print("--- Describe registro_diario ---")
    cur.execute("DESCRIBE registro_diario")
    for row in cur.fetchall():
        if row['Field'] == 'estado_documento':
            print(f"Column: {row['Field']}, Type: {row['Type']}")
            
    # 2. Count current states
    print("\n--- Current States ---")
    cur.execute("SELECT estado_documento, COUNT(*) as c FROM registro_diario GROUP BY estado_documento")
    for row in cur.fetchall():
        print(f"State: {row['estado_documento']}, Count: {row['c']}")
        
    # 3. Try to update one NULL record to 'NO UTILIZADO'
    print("\n--- Test Update ---")
    # Find a record that is NULL or INUTILIZADO
    cur.execute("SELECT id, gestion, nro_comprobante FROM registro_diario WHERE estado_documento IS NULL LIMIT 1")
    row = cur.fetchone()
    if row:
        print(f"Found candidate: ID {row['id']}, Gestion {row['gestion']}, Comprobante {row['nro_comprobante']}")
        try:
            cur.execute("UPDATE registro_diario SET estado_documento='NO UTILIZADO' WHERE id=%s", (row['id'],))
            print(f"Update executed. Rows affected: {cur.rowcount}")
            conn.commit()
            print("Commit successful.")
        except Exception as e:
            print(f"Update failed: {e}")
    else:
        print("No NULL records found.")
        
    conn.close()

except Exception as e:
    print(f"Connection failed: {e}")
