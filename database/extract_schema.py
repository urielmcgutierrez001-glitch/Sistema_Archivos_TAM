import psycopg2
import json

# Pooler Connection
SUPABASE_URI = "postgresql://postgres.sdovwowdbuzjfwtgnfoa:pasantiatam123@aws-1-us-east-1.pooler.supabase.com:6543/postgres"

def get_schema_info():
    schema_info = {}
    try:
        conn = psycopg2.connect(SUPABASE_URI)
        cursor = conn.cursor()
        
        # Get all tables
        cursor.execute("""
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public'
        """)
        tables = [row[0] for row in cursor.fetchall()]
        
        for table in tables:
            table_data = {'columns': [], 'fks': [], 'pks': []}
            
            # Get Columns
            cursor.execute(f"""
                SELECT column_name, data_type, is_nullable
                FROM information_schema.columns 
                WHERE table_name = '{table}'
            """)
            for col in cursor.fetchall():
                table_data['columns'].append({
                    'name': col[0],
                    'type': col[1],
                    'nullable': col[2] == 'YES'
                })
                
            # Get PKs
            cursor.execute(f"""
                SELECT c.column_name
                FROM information_schema.table_constraints tc 
                JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                JOIN information_schema.columns c ON c.table_name = tc.table_name AND c.column_name = ccu.column_name
                WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_name = '{table}'
            """)
            table_data['pks'] = [row[0] for row in cursor.fetchall()]

            # Get FKs
            cursor.execute(f"""
                SELECT
                    kcu.column_name, 
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name 
                FROM 
                    information_schema.key_column_usage AS kcu
                    JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = kcu.constraint_name
                WHERE kcu.table_name = '{table}'
            """)
            for fk in cursor.fetchall():
                table_data['fks'].append({
                    'column': fk[0],
                    'foreign_table': fk[1],
                    'foreign_column': fk[2]
                })

            schema_info[table] = table_data
            
        conn.close()
        print(json.dumps(schema_info, indent=2))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    get_schema_info()
