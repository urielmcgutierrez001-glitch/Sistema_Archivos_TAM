import psycopg2
import os

# Pooler Connection
SUPABASE_URI = "postgresql://postgres.sdovwowdbuzjfwtgnfoa:pasantiatam123@aws-1-us-east-1.pooler.supabase.com:6543/postgres"

def run_sql_file(filename):
    print(f"Executing {filename}...")
    try:
        conn = psycopg2.connect(SUPABASE_URI)
        cursor = conn.cursor()
        
        with open(filename, 'r', encoding='utf-8') as f:
            sql_content = f.read()
            
        cursor.execute(sql_content)
        conn.commit()
        
        print("✅ Cleanup executed successfully.")
        conn.close()
        
    except Exception as e:
        print(f"❌ Error executing script: {e}")
        if 'conn' in locals() and conn:
            conn.rollback()

if __name__ == "__main__":
    base_dir = os.path.dirname(os.path.abspath(__file__))
    sql_file = os.path.join(base_dir, "cleanup_redundancies.sql")
    run_sql_file(sql_file)
