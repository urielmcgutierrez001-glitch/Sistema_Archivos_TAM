
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
ARCHIVO = "08 HOJAS DE RUTA - DIARIOS TAMEP ARCHIVOS 2007 - 2026.xlsx"
TABLA = "registro_hojas_ruta"
# No hay 'tipo_documento' en esta tabla, se asume HOJA_RUTA_DIARIOS o similar implícito para contenedores

# Mapping headers based on inspection
COL_COMPROBANTE = "NRO. DE COMPROBANTE DIARIO"
COL_HR = "NRO. \nHOJA DE RUTA"
COL_GESTION_HR = "GESTION (H.R.)"
COL_CONTENEDOR = "NRO. LIBRO\nAMARR"
COL_UBICACION = "LUGAR DE ARCHIVO"

# Mapeo de ubicaciones
UBICACIONES_MAP = {
    'ALMACENES': 9,
    'CONTRATACIONES': 6,
    'EL ALTO': 2,
    'ENCOMIENDAS': 1,
    'INFORMATICA': 20,
    'INFORMATICA 2': 19,
    'REVISION': 3,
    'REVISIÓN': 3,
    'SAL CONTA': 14,
    'SALA CONTA': 21,
    'SECC. CONTABILIDAD': 12,
    'SECC. JEFE DE CONTABILIDAD': 11,
}

def conectar_db():
    return pymysql.connect(**DB_CONFIG)

def encontrar_columna_flexible(df, keyword):
    for col in df.columns:
        if keyword in str(col).replace('\n', ' '):
            return col
    return None

def normalizar_nro_comprobante(valor):
    if pd.isna(valor): return None
    valor_str = str(valor).strip()
    match = re.match(r'^[A-Za-z]-?0*(\d+)$', valor_str)
    if match: return match.group(1)
    match = re.match(r'^0*(\d+)$', valor_str)
    if match: return match.group(1)
    try: return str(int(float(valor_str)))
    except: return valor_str

def normalizar_numero_contenedor(valor):
    if pd.isna(valor): return None
    valor_str = str(valor).strip()
    match = re.match(r'^L\s*-?\s*(\d+)$', valor_str, re.IGNORECASE)
    if match: return match.group(1)
    try: return str(int(float(valor_str)))
    except: return valor_str

def get_ubicacion_id(nombre):
    if pd.isna(nombre): return None
    nombre = str(nombre).strip().upper()
    for key, val in UBICACIONES_MAP.items():
        if key in nombre: return val
    return None

def main():
    print(f"Iniciando REPARACION de {TABLA}...")
    conn = conectar_db()
    cursor = conn.cursor()
    
    ruta = os.path.join(BASE_PATH, ARCHIVO)
    df = pd.read_excel(ruta)
    
    # Headers flexibles
    col_comp = encontrar_columna_flexible(df, "COMPROBANTE")
    col_hr = encontrar_columna_flexible(df, "HOJA DE RUTA")
    col_ghr = encontrar_columna_flexible(df, "GESTION (H.R.)")
    col_cont = encontrar_columna_flexible(df, "LIBRO")
    col_ubi = encontrar_columna_flexible(df, "LUGAR")
    
    print(f"Cols: Comp={col_comp}, HR={col_hr}, GHR={col_ghr}, Cont={col_cont}, Ubi={col_ubi}")
    
    # Cache to track processed IDs to prevent overwriting
    processed_ids = set()
    
    updated_data = 0
    created_containers = 0
    linked_docs = 0
    
    contenedores_cache = {}
    
    for index, row in df.iterrows():
        gestion = row.get('GESTION')
        if pd.isna(gestion): continue
        
        comp_raw = row.get(col_comp)
        comp_norm = normalizar_nro_comprobante(comp_raw)
        if not comp_norm: continue
        
        # Additional matching criteria
        conam_val = row.get('CONAM') # if col exists
        conam_str = str(conam_val).strip() if pd.notna(conam_val) else None
        
        # 1. Buscar Documentos Candidatos (NO PROCESADOS)
        # Note: We fetch ALL matches, then pick the first one not in processed_ids
        # Adding 'conam' to query if useful, but schema check showed 'conam' exists
        
        sql_find = f"""
            SELECT id, contenedor_fisico_id, nro_hoja_ruta, gestion_hr, conam, rubro, interesado 
            FROM {TABLA} 
            WHERE gestion=%s AND (nro_comprobante_diario=%s OR nro_comprobante_diario=%s)
        """
        cursor.execute(sql_find, (gestion, comp_raw, comp_norm))
        candidates = cursor.fetchall()
        
        target_doc = None
        
        # Filter candidates:
        # 1. Must not be in processed_ids
        # 2. Prefer match on CONAM if available
        # 3. Prefer match on Rubro/Interesado if avail (fuzzy)
        
        available_candidates = [d for d in candidates if d['id'] not in processed_ids]
        
        if not available_candidates:
            # print(f"No free doc found for {gestion}-{comp_norm}")
            continue
            
        # Try finding by CONAM match first
        if conam_str:
            for cand in available_candidates:
                cand_conam = cand.get('conam')
                if cand_conam and str(cand_conam).strip() == conam_str:
                    target_doc = cand
                    break
        
        # Fallback: Just take the first available
        if not target_doc:
            target_doc = available_candidates[0]
            
        doc_id = target_doc['id']
        processed_ids.add(doc_id)
        current_cont_id = target_doc['contenedor_fisico_id']
        
        # 2. Actualizar Datos (HR y Gestion HR)
        hr_val = row.get(col_hr)
        ghr_val = row.get(col_ghr)
        
        hr_val = str(hr_val).strip() if pd.notna(hr_val) else None
        ghr_val = int(ghr_val) if pd.notna(ghr_val) else None
        
        if hr_val or ghr_val:
            try:
                cursor.execute(f"UPDATE {TABLA} SET nro_hoja_ruta=%s, gestion_hr=%s WHERE id=%s", (hr_val, ghr_val, doc_id))
                if cursor.rowcount > 0:
                    updated_data += 1
            except pymysql.err.IntegrityError:
                print(f"  Warning: Doc {doc_id} duplicate key violation for HR {hr_val}. Skipping HR update.")
            except Exception as e:
                print(f"  Error updating doc {doc_id}: {e}")
                
        # 3. Contenedores
        cont_num_raw = row.get(col_cont)
        cont_num = normalizar_numero_contenedor(cont_num_raw)
        
        if cont_num:
            tipo_cont = 'LIBRO' 
            tipo_doc_cont = 'HOJA_RUTA_DIARIOS' 
            
            clave = f"{tipo_cont}-{cont_num}-{tipo_doc_cont}"
            
            if clave in contenedores_cache:
                new_conten_id = contenedores_cache[clave]
            else:
                # Buscar existente
                sql_cont = "SELECT id FROM contenedores_fisicos WHERE tipo_contenedor=%s AND numero=%s AND tipo_documento=%s"
                cursor.execute(sql_cont, (tipo_cont, cont_num, tipo_doc_cont))
                res_cont = cursor.fetchone()
                
                if res_cont:
                    new_conten_id = res_cont['id']
                else:
                    # Crear
                    ubi_raw = row.get(col_ubi)
                    ubi_id = get_ubicacion_id(ubi_raw)
                    
                    sql_ins = "INSERT INTO contenedores_fisicos (tipo_contenedor, tipo_documento, numero, ubicacion_id, activo) VALUES (%s, %s, %s, %s, 1)"
                    cursor.execute(sql_ins, (tipo_cont, tipo_doc_cont, cont_num, ubi_id))
                    new_conten_id = cursor.lastrowid
                    created_containers += 1
                
                contenedores_cache[clave] = new_conten_id
            
            # 4. Linkear
            if current_cont_id != new_conten_id:
                cursor.execute(f"UPDATE {TABLA} SET contenedor_fisico_id=%s WHERE id=%s", (new_conten_id, doc_id))
                linked_docs += 1
                
    conn.commit()
    conn.close()
    
    print(f"Finalizado.")
    print(f"  Datos HR actualizados: {updated_data}")
    print(f"  Contenedores creados: {created_containers}")
    print(f"  Docs vinculados a contenedor: {linked_docs}")

if __name__ == "__main__":
    main()
