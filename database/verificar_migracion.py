import psycopg2
import sys

# Pooler Connection (IPv4 compatible)
SUPABASE_URI = "postgresql://postgres.sdovwowdbuzjfwtgnfoa:pasantiatam123@aws-1-us-east-1.pooler.supabase.com:6543/postgres"

def verify():
    try:
        conn = psycopg2.connect(SUPABASE_URI)
        cursor = conn.cursor()
        
        tables = ['ubicaciones', 'tipos_contenedor', 'contenedores_fisicos', 'documentos', 'tipo_documento']
        
        print("="*40)
        print("VERIFICACIÓN DE DATOS EN SUPABASE")
        print("="*40)
        
        for table in tables:
            try:
                cursor.execute(f'SELECT COUNT(*) FROM "{table}"')
                count = cursor.fetchone()[0]
                print(f"✅ {table}: {count} registros")
            except Exception as e:
                print(f"❌ {table}: Error ({e})")
                conn.rollback()
                
        conn.close()
        
    except Exception as e:
        print(f"❌ Error de conexión: {e}")

if __name__ == "__main__":
    verify()
