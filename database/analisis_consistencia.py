
import pymysql

# Database Configuration
config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'cursorclass': pymysql.cursors.DictCursor
}

def analyze_gestion_consistency(connection):
    print("\n--- Analizando consistencia de GESTIÓN ---")
    query = """
    SELECT 
        d.id as doc_id, 
        d.nro_comprobante, 
        COALESCE(td.codigo, 'N/A') as tipo_doc,
        d.gestion as doc_gestion, 
        c.numero as cont_numero, 
        c.gestion as cont_gestion
    FROM documentos d
    JOIN contenedores_fisicos c ON d.contenedor_fisico_id = c.id
    LEFT JOIN tipo_documento td ON d.tipo_documento_id = td.id
    WHERE d.gestion != c.gestion
    ORDER BY c.numero, d.nro_comprobante
    LIMIT 20
    """
    try:
        with connection.cursor() as cursor:
            cursor.execute(query)
            results = cursor.fetchall()
            
            if not results:
                print("✅ Todos los documentos coinciden en GESTIÓN con su contenedor.")
            else:
                print(f"❌ Se encontraron inconsistencias en GESTIÓN (mostrando hasta 20):")
                print(f"{'Doc ID':<8} {'Nro Comp':<10} {'Tipo Doc':<10} {'Ges Doc':<10} {'Nro Cont':<10} {'Ges Cont':<10}")
                print("-" * 65)
                for row in results:
                    print(f"{row['doc_id']:<8} {row['nro_comprobante']:<10} {row['tipo_doc']:<10} {row['doc_gestion']:<10} {row['cont_numero']:<10} {row['cont_gestion']:<10}")
    except Exception as e:
        print(f"Error querying gestion: {e}")

def analyze_document_types(connection):
    print("\n--- Analizando tipos de documentos por contenedor ---")
    query = """
    SELECT 
        c.id as cont_id, 
        c.numero as cont_numero, 
        c.gestion as cont_gestion,
        COUNT(DISTINCT d.tipo_documento_id) as tipos_count,
        GROUP_CONCAT(DISTINCT COALESCE(td.codigo, 'Unknown')) as tipos_encontrados
    FROM contenedores_fisicos c
    JOIN documentos d ON c.id = d.contenedor_fisico_id
    LEFT JOIN tipo_documento td ON d.tipo_documento_id = td.id
    GROUP BY c.id
    HAVING tipos_count > 1
    ORDER BY c.numero
    """
    try:
        with connection.cursor() as cursor:
            cursor.execute(query)
            results = cursor.fetchall()
            
            if not results:
                print("✅ Todos los contenedores tienen un ÚNICO tipo de documento.")
            else:
                print(f"⚠️ Se encontraron {len(results)} contenedores con MÚLTIPLES tipos de documentos:")
                print(f"{'Cont ID':<8} {'Nro Cont':<10} {'Gestión':<10} {'Tipos Count':<12} {'Tipos Encontrados'}")
                print("-" * 70)
                for row in results:
                    print(f"{row['cont_id']:<8} {row['cont_numero']:<10} {row['cont_gestion']:<10} {row['tipos_count']:<12} {row['tipos_encontrados']}")
    except Exception as e:
        print(f"Error querying document types: {e}")

try:
    conn = pymysql.connect(**config)
    analyze_gestion_consistency(conn)
    analyze_document_types(conn)
    conn.close()
except Exception as e:
    print(f"Error connecting: {e}")
