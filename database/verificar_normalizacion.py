import psycopg2
import sys

# Pooler Connection
SUPABASE_URI = "postgresql://postgres.sdovwowdbuzjfwtgnfoa:pasantiatam123@aws-1-us-east-1.pooler.supabase.com:6543/postgres"

def verify():
    try:
        conn = psycopg2.connect(SUPABASE_URI)
        cursor = conn.cursor()
        
        print("VERIFICANDO NORMALIZACIÓN")
        print("-------------------------")

        # 1. Verificar Tabla Estados
        try:
            cursor.execute('SELECT * FROM "estados"')
            estados = cursor.fetchall()
            print(f"✅ Tabla 'estados' existe con {len(estados)} registros.")
            for e in estados:
                print(f"   - {e[1]}")
        except Exception as e:
            print(f"❌ Error tabla 'estados': {e}")

        # 2. Verificar Documentos (estado_documento_id)
        try:
            cursor.execute('SELECT COUNT(*) FROM "documentos" WHERE "estado_documento_id" IS NOT NULL')
            count = cursor.fetchone()[0]
            print(f"✅ Documentos con estado_id asignado: {count}")
        except Exception as e:
            print(f"❌ Error columna 'estado_documento_id' en documentos: {e}")

        # 3. Verificar Contenedores (tipo_contenedor_id)
        try:
            cursor.execute('SELECT COUNT(*) FROM "contenedores_fisicos" WHERE "tipo_contenedor_id" IS NOT NULL')
            count = cursor.fetchone()[0]
            print(f"✅ Contenedores con tipo_id asignado: {count}")
        except Exception as e:
            print(f"❌ Error columna 'tipo_contenedor_id' en contenedores: {e}")

        # 4. Verificar Prestamos (estado_anterior_id)
        try:
            cursor.execute('SELECT column_name FROM information_schema.columns WHERE table_name = \'prestamos\' AND column_name = \'estado_anterior_id\'')
            if cursor.fetchone():
                print("✅ Columna 'estado_anterior_id' existe en prestamos.")
            else:
                print("❌ Columna 'estado_anterior_id' NO existe en prestamos.")
        except Exception as e:
             print(f"❌ Error verificando prestamos: {e}")

        conn.close()
        
    except Exception as e:
        print(f"❌ Error de conexión: {e}")

if __name__ == "__main__":
    verify()
