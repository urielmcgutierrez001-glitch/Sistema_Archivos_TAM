
import pandas as pd
import pymysql
import re
import os

# Configuración de BD
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

BASE_PATH = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

# Define mappings for each file
# Table: registro_diario or registro_hojas_ruta
ARCHIVOS = [
    {
        "archivo": "01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_DIARIO",
        "col_comprobante": "NRO. COMPROBANTE",
        "col_ubicacion": "BLOQUE \nNIVEL"
    },
    {
        "archivo": "02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_INGRESO",
        "col_comprobante": "NRO. COMPROBANTE DE CONTABILIDAD INGRESO",
        "col_ubicacion": "BLOQUE / NIVEL"
    },
    {
        "archivo": "03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_CEPS",
        "col_comprobante": "NRO COMPROBANTE DE CONTABILIDAD EGRESO",
        "col_ubicacion": "BLOQUE\nNIVEL"
    },
    {
        "archivo": "04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "PREVENTIVOS",
        "col_comprobante": "NRO. DE PREVENTIVO",
        "col_ubicacion": "BLOQUE\nNIVEL"
    },
    {
        "archivo": "05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "ASIENTOS_MANUALES",
        "col_comprobante": "NRO. COMPROBANTE DE CONTABILIDAD MANUAL",
        "col_ubicacion": "BLOQUE / NIVEL"
    },
    {
        "archivo": "06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "DIARIOS_APERTURA",
        "col_comprobante": "NRO. COMPROBANTE DE CONTABILIDAD TRASPASO",
        "col_ubicacion": "BLOQUE / NIVEL"
    },
    {
        "archivo": "07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_TRASPASO",
        "col_comprobante": "COMPROBANTE DE CONTABILIDAD TRASPASO",
        "col_ubicacion": "BLOQUE / NIVEL"
    },
    {
        "archivo": "08 HOJAS DE RUTA - DIARIOS TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_hojas_ruta",
        "tipo": "HOJA_RUTA_DIARIOS",
        "col_comprobante": "NRO. DE COMPROBANTE DIARIO",
        "col_ubicacion": "LUGAR DE ARCHIVO"
    }
]

def normalizar_nro_comprobante(valor):
    if pd.isna(valor):
        return None
    valor_str = str(valor).strip()
    match = re.match(r'^[A-Za-z]-?0*(\d+)$', valor_str)
    if match: return match.group(1)
    match = re.match(r'^0*(\d+)$', valor_str)
    if match: return match.group(1)
    try:
        return str(int(float(valor_str)))
    except:
        return valor_str

def conectar_db():
    return pymysql.connect(**DB_CONFIG)

def verificar_crear_columna(conn, tabla, columna):
    try:
        with conn.cursor() as cursor:
            cursor.execute(f"SHOW COLUMNS FROM {tabla} LIKE '{columna}'")
            if not cursor.fetchone():
                print(f"Creando columna {columna} en tabla {tabla}...")
                cursor.execute(f"ALTER TABLE {tabla} ADD COLUMN {columna} VARCHAR(255) NULL")
                conn.commit()
    except Exception as e:
        print(f"Error verificando columna en {tabla}: {e}")

def procesar_archivo(config, conn):
    archivo = config['archivo']
    ruta = os.path.join(BASE_PATH, archivo)
    tipo_doc = config['tipo']
    col_comp = config['col_comprobante']
    col_ubi = config['col_ubicacion']
    tabla = config['tabla']
    
    print(f"\nProcesando {archivo} ({tabla})...")
    
    try:
        df = pd.read_excel(ruta)
        cursor = conn.cursor()
        
        actualizados = 0
        no_encontrados = 0
        
        # Determine column names for query
        if tabla == 'registro_hojas_ruta':
            col_db_comp = 'nro_comprobante_diario'
            col_db_ubi = 'ubicacion_fisica' # Assuming standardized name
            # Hojas ruta might not have 'tipo_documento' column or it might be implicit
            sql_update = """
                UPDATE registro_hojas_ruta 
                SET ubicacion_fisica = %s 
                WHERE gestion = %s AND (nro_comprobante_diario = %s OR nro_comprobante_diario = %s)
            """
        else:
            col_db_comp = 'nro_comprobante'
            col_db_ubi = 'ubicacion_fisica'
            sql_update = """
                UPDATE registro_diario 
                SET ubicacion_fisica = %s 
                WHERE gestion = %s AND (nro_comprobante = %s OR nro_comprobante = %s) AND tipo_documento = %s
            """

        for index, row in df.iterrows():
            gestion = row.get('GESTION')
            # Handle headers with potential spaces or newlines if exact match failed previously?
            # We assume config['col_ubicacion'] is correct based on inspection.
            
            # Use column name from config, but be flexible if pandas normalized it differently? 
            # Pandas usually preserves it unless duplicates.
            
            if col_comp not in df.columns or col_ubi not in df.columns:
                # Try finding column loosely
                found_comp = next((c for c in df.columns if col_comp.replace('\n', ' ') in c.replace('\n', ' ')), col_comp)
                found_ubi = next((c for c in df.columns if col_ubi.replace('\n', ' ') in c.replace('\n', ' ')), col_ubi)
            else:
                found_comp = col_comp
                found_ubi = col_ubi

            comprobante = row.get(found_comp)
            ubicacion = row.get(found_ubi)
            
            if pd.isna(gestion) or pd.isna(comprobante) or pd.isna(ubicacion):
                continue
                
            nro_norm = normalizar_nro_comprobante(comprobante)
            if not nro_norm: continue
            
            ubicacion_str = str(ubicacion).strip()
            
            if tabla == 'registro_hojas_ruta':
                filas = cursor.execute(sql_update, (ubicacion_str, gestion, comprobante, nro_norm))
            else:
                filas = cursor.execute(sql_update, (ubicacion_str, gestion, comprobante, nro_norm, tipo_doc))
            
            if filas > 0:
                actualizados += 1
            else:
                no_encontrados += 1
                
        conn.commit()
        print(f"  -> Actualizados: {actualizados}")
        print(f"  -> No encontrados/Sin cambios: {no_encontrados}")
        
    except Exception as e:
        print(f"  -> Error procesando archivo: {e}")

def main():
    print("Iniciando importación FALLBACK de ubicaciones para APERTURA (directo en registro_diario)...")
    try:
        conn = conectar_db()
    except Exception as e:
        print(f"Error conectando a BD: {e}")
        return

    # Ensure columns exist
    verificar_crear_columna(conn, 'registro_diario', 'ubicacion_fisica')

    for config in ARCHIVOS:
        if "APERTURA" not in config['tipo']: continue 
        procesar_archivo(config, conn)
    
    conn.close()
    print("\nProceso finalizado.")

if __name__ == "__main__":
    main()
