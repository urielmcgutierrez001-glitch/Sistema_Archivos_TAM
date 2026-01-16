import pymysql
import json

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'cursorclass': pymysql.cursors.DictCursor
}

def verificar():
    try:
        conn = pymysql.connect(**DB_CONFIG)
        with conn.cursor() as cursor:
            print("=== VERIFICACIÓN DE IMPORTACIÓN FINAL ===")
            
            # 1. Contenedores
            cursor.execute("SELECT COUNT(*) as c FROM contenedores_fisicos")
            contenedores = cursor.fetchone()['c']
            print(f"\n📦 Contenedores Físicos creados: {contenedores}")
            
            cursor.execute("SELECT COUNT(*) as c FROM documentos WHERE contenedor_fisico_id IS NOT NULL")
            docs_con_contenedor = cursor.fetchone()['c']
            print(f"📄 Documentos vinculados a contenedor: {docs_con_contenedor}")
            
            # 2. Estados
            print(f"\n📊 Distribución de Estados:")
            cursor.execute("SELECT estado_documento, COUNT(*) as c FROM documentos GROUP BY estado_documento")
            for row in cursor.fetchall():
                print(f"   - {row['estado_documento']}: {row['c']}")
                
            # 3. Observaciones (Muestra)
            print(f"\n📝 Muestra de Observaciones (5 ejemplos):")
            cursor.execute("SELECT nro_comprobante, observaciones FROM documentos WHERE observaciones IS NOT NULL AND observaciones != '' LIMIT 5")
            for row in cursor.fetchall():
                print(f"   - Doc {row['nro_comprobante']}: {row['observaciones']}")
                
            # 4. Hojas de ruta (Atributos extra)
            print(f"\n🚚 Hojas de Ruta (Atributos JSON):")
            cursor.execute("SELECT COUNT(*) as c FROM documentos WHERE tipo_documento = 'HOJA_RUTA_DIARIOS' AND atributos_extra IS NOT NULL")
            hr_validas = cursor.fetchone()['c']
            print(f"   - Registros con JSON válido: {hr_validas}")
            
            cursor.execute("SELECT atributos_extra FROM documentos WHERE tipo_documento = 'HOJA_RUTA_DIARIOS' LIMIT 1")
            ejemplo = cursor.fetchone()
            if ejemplo:
                print(f"   - Ejemplo JSON: {ejemplo['atributos_extra']}")

        conn.close()
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    verificar()
