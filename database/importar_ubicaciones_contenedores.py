
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

# Mapeo manual de nombres a IDs (basado en fetch_ubicaciones.py)
UBICACIONES_MAP = {
    'ALMACENES': 9,
    'CONTRATACIONES': 6,
    'EL ALTO': 2,
    'ENCOMIENDAS': 1,
    'ENCOMIENDA': 1,
    'INFORMATICA': 20,
    'INFORMATICA 2': 19,
    'REVISION': 3,
    'REVISIÓN': 3,
    'SAL CONTA': 14,
    'SALA CONTA': 21,
    'SECC. CONTABILIDAD': 12,
    'SECC. JEFE DE CONTABILIDAD': 11,
    'CONTRATACIONES Y ADQUISICIONES': 6,
    'ENCOMIENDAS 1': 1,
    'ENCOMIENDA 1': 1,
    # Agregar más según se necesite
    'TESORERÍA': None, # No existe ID claro
    'ARCHIVOS': None
}

ARCHIVOS = [
    {
        "archivo": "01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_DIARIO",
        "col_comprobante": "NRO. COMPROBANTE",
        "col_ubicacion_nombre": "Ubicación Unidad/Área",
        "col_bloque": "BLOQUE \nNIVEL"
    },
    {
        "archivo": "02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_INGRESO",
        "col_comprobante": "NRO. COMPROBANTE DE CONTABILIDAD INGRESO",
        "col_ubicacion_nombre": "Ubicación Unidad/Área",
        "col_bloque": "BLOQUE / NIVEL"
    },
    {
        "archivo": "03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_CEPS",
        "col_comprobante": "NRO COMPROBANTE DE CONTABILIDAD EGRESO",
        "col_ubicacion_nombre": "Ubicación Unidad/Área",
        "col_bloque": "BLOQUE\nNIVEL"
    },
    {
        "archivo": "04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "PREVENTIVOS",
        "col_comprobante": "NRO. DE PREVENTIVO",
        "col_ubicacion_nombre": "Ubicación Unidad/Área", 
        "col_bloque": "BLOQUE\nNIVEL"
    },
    {
        "archivo": "05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "ASIENTOS_MANUALES",
        "col_comprobante": "NRO. COMPROBANTE DE CONTABILIDAD MANUAL",
        "col_ubicacion_nombre": "Ubicación Unidad/Área",
        "col_bloque": "BLOQUE / NIVEL"
    },
    {
        "archivo": "06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "DIARIOS_APERTURA",
        "col_comprobante": "NRO. COMPROBANTE DE CONTABILIDAD TRASPASO",
        "col_ubicacion_nombre": "Ubicación Unidad/Área",
        "col_bloque": "BLOQUE / NIVEL"
    },
    {
        "archivo": "07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_diario",
        "tipo": "REGISTRO_TRASPASO",
        "col_comprobante": "COMPROBANTE DE CONTABILIDAD TRASPASO",
        "col_ubicacion_nombre": "Ubicación Unidad/Área",
        "col_bloque": "BLOQUE / NIVEL"
    },
    {
        "archivo": "08 HOJAS DE RUTA - DIARIOS TAMEP ARCHIVOS 2007 - 2026.xlsx",
        "tabla": "registro_hojas_ruta",
        "tipo": "HOJA_RUTA_DIARIOS",
        "col_comprobante": "NRO. DE COMPROBANTE DIARIO",
        "col_ubicacion_nombre": "OBS.", 
        "col_bloque": "LUGAR DE ARCHIVO" 
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

def get_ubicacion_id(nombre):
    if pd.isna(nombre): return None
    nombre = str(nombre).strip().upper()
    
    # Direct Key match
    if nombre in UBICACIONES_MAP:
        return UBICACIONES_MAP[nombre]
    
    # Fuzzy / Contains
    for key, val in UBICACIONES_MAP.items():
        if key in nombre: # e.g. "INFORMATICA 1" contains "INFORMATICA"
            return val
            
    return None

def conectar_db():
    return pymysql.connect(**DB_CONFIG)

def procesar_archivo(config, conn):
    archivo = config['archivo']
    ruta = os.path.join(BASE_PATH, archivo)
    tipo_doc = config['tipo']
    col_comp = config['col_comprobante']
    
    col_bloque = config.get('col_bloque')
    col_ubi_nom = config.get('col_ubicacion_nombre')
    tabla = config['tabla']
    
    print(f"\nProcesando {archivo} ({tabla})...")
    
    try:
        df = pd.read_excel(ruta)
        cursor = conn.cursor()
        
        actualizados = 0
        sin_contenedor = 0
        no_encontrados_doc = 0
        
        # SQL para encontrar documento y su contenedor
        if tabla == 'registro_hojas_ruta':
            sql_find = "SELECT id, contenedor_fisico_id FROM registro_hojas_ruta WHERE gestion = %s AND (nro_comprobante_diario = %s OR nro_comprobante_diario = %s)"
        else:
            sql_find = "SELECT id, contenedor_fisico_id FROM registro_diario WHERE gestion = %s AND (nro_comprobante = %s OR nro_comprobante = %s) AND tipo_documento = %s"
            
        
        for index, row in df.iterrows():
            gestion = row.get('GESTION')
            
            # Find columns dynamically if needed (newlines etc)
            found_comp = next((c for c in df.columns if col_comp.replace('\n', ' ') in c.replace('\n', ' ')), col_comp)
            comprobante = row.get(found_comp)

            bloque = None
            if col_bloque:
                found_bloque = next((c for c in df.columns if col_bloque.replace('\n', ' ') in c.replace('\n', ' ')), col_bloque)
                if found_bloque in df.columns:
                    bloque = row.get(found_bloque)
            
            ubicacion_nombre = None
            if col_ubi_nom:
                 found_ubi = next((c for c in df.columns if col_ubi_nom.replace('\n', ' ') in c.replace('\n', ' ')), col_ubi_nom)
                 if found_ubi in df.columns:
                     ubicacion_nombre = row.get(found_ubi)
            
            if pd.isna(gestion) or pd.isna(comprobante):
                continue
                
            nro_norm = normalizar_nro_comprobante(comprobante)
            if not nro_norm: continue
            
            # 1. Buscar Documento
            if tabla == 'registro_hojas_ruta':
                cursor.execute(sql_find, (gestion, comprobante, nro_norm))
            else:
                cursor.execute(sql_find, (gestion, comprobante, nro_norm, tipo_doc))
            
            doc = cursor.fetchone()
            
            if not doc:
                no_encontrados_doc += 1
                continue
                
            conten_id = doc['contenedor_fisico_id']
            if not conten_id:
                sin_contenedor += 1
                continue
            
            # 2. Preparar valores de actualización
            bloque_str = str(bloque).strip() if pd.notna(bloque) else None
            ubi_id = get_ubicacion_id(ubicacion_nombre)
            
            if bloque_str is None and ubi_id is None:
                continue
            
            # 3. Actualizar Contenedor
            updates = []
            params = []
            if bloque_str is not None:
                updates.append("bloque_nivel = %s")
                params.append(bloque_str)
            if ubi_id is not None:
                updates.append("ubicacion_id = %s")
                params.append(ubi_id)
            
            if not updates: continue
            
            sql = f"UPDATE contenedores_fisicos SET {', '.join(updates)} WHERE id = %s"
            params.append(conten_id)
            
            res = cursor.execute(sql, tuple(params))
            if res > 0:
                actualizados += 1
                
        conn.commit()
        print(f"  -> Docs procesados con contenedor: {actualizados} actualizaciones")
        print(f"  -> Docs sin contenedor (skip): {sin_contenedor}")
        print(f"  -> Docs no encontrados en BD: {no_encontrados_doc}")
        
    except Exception as e:
        print(f"  -> Error procesando archivo: {e}")

def main():
    print("Iniciando importación GLOBAL de ubicaciones hacia CONTENEDORES FISICOS...")
    try:
        conn = conectar_db()
    except Exception as e:
        print(f"Error conectando a BD: {e}")
        return

    for config in ARCHIVOS:
        procesar_archivo(config, conn)
    
    conn.close()
    print("\nProceso finalizado.")

if __name__ == "__main__":
    main()
