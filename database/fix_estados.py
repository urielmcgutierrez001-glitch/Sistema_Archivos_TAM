
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
    print("Connecting to DB...")
    conn = pymysql.connect(**DB_CONFIG)
    cur = conn.cursor()
    
    # 1. Check Enum
    print("Checking ENUM definition...")
    cur.execute("DESCRIBE registro_diario")
    enum_type = ""
    for row in cur.fetchall():
        if row['Field'] == 'estado_documento':
            enum_type = row['Type']
            print(f"Current Type: {enum_type}")
            
    if "NO UTILIZADO" not in enum_type:
        print("Adding 'NO UTILIZADO' to ENUM...")
        # Construct new enum string. Be careful to parse the old one correctly or just replace.
        # Simplest is to just set the full new definition.
        sql = "ALTER TABLE registro_diario MODIFY COLUMN estado_documento ENUM('DISPONIBLE','FALTA','PRESTADO','ANULADO','NO UTILIZADO') NULL"
        cur.execute(sql)
        print("ALTER TABLE executed.")
    else:
        print("'NO UTILIZADO' already in ENUM.")
        
    # 2. Update Records
    print("Updating empty/NULL records...")
    # Update empty strings
    cur.execute("UPDATE registro_diario SET estado_documento='NO UTILIZADO' WHERE estado_documento=''")
    print(f"Updated {cur.rowcount} empty records.")
    
    # Update NULLs (just in case)
    # cur.execute("UPDATE registro_diario SET estado_documento='NO UTILIZADO' WHERE estado_documento IS NULL") 
    # print(f"Updated {cur.rowcount} NULL records.")
    
    conn.commit()
    print("Changes committed.")
    
    # 3. Verify
    print("\n--- Final Counts ---")
    cur.execute("SELECT estado_documento, COUNT(*) as c FROM registro_diario GROUP BY estado_documento")
    for row in cur.fetchall():
        print(f"State: {row['estado_documento']}, Count: {row['c']}")
        
    conn.close()

except Exception as e:
    print(f"ERROR: {e}")
