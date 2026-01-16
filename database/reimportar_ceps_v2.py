
import pandas as pd
import pymysql
import re
import os
import json

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
ARCHIVO = "03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx"
TABLA_DESTINO = "documentos"
TIPO_DOC_STR = "REGISTRO_CEPS"
TIPO_DOC_ID = 3

def conectar_db():
    return pymysql.connect(**DB_CONFIG)

def normalizar_nro_comprobante(valor):
    if pd.isna(valor): return None
    valor_str = str(valor).strip()
    match = re.match(r'^[A-Za-z]-?0*(\d+)$', valor_str)
    if match: return match.group(1)
    match = re.match(r'^0*(\d+)$', valor_str)
    if match: return match.group(1)
    try: return str(int(float(valor_str)))
    except: return valor_str

def encontrar_columna(df, patrones):
    for col in df.columns:
        col_limpio = str(col).strip().upper().replace('\n', ' ')
        if isinstance(patrones, list):
            for p in patrones:
                if p in col_limpio: return col
        else:
            if patrones in col_limpio: return col
    return None

def main():
    print(f"Iniciando RE-IMPORTACION de {ARCHIVO} a {TABLA_DESTINO}...")
    conn = conectar_db()
    cursor = conn.cursor()
    
    ruta = os.path.join(BASE_PATH, ARCHIVO)
    df = pd.read_excel(ruta)
    
    # Headers
    col_comp = encontrar_columna(df, "COMPROBANTE")
    col_gestion = "GESTION"
    
    # Optional columns to update
    col_abc = encontrar_columna(df, ["ABC", "CODIGO"])
    col_obs = encontrar_columna(df, "OBSERVACIONES")
    col_estado_perdido = encontrar_columna(df, "PERDIDO")
    
    print(f"Cols: Comp={col_comp}, Gestion={col_gestion}, ABC={col_abc}")
    
    updated = 0
    not_found = 0
    
    for index, row in df.iterrows():
        gestion = row.get(col_gestion)
        if pd.isna(gestion): continue
        
        comp_raw = row.get(col_comp)
        comp_norm = normalizar_nro_comprobante(comp_raw)
        if not comp_norm: continue
        
        # 1. Update query
        # We need to find the doc. User says "el registro_ceps tiene id 3 en FK"
        # Since we are re-importing, strictly we should match by Key.
        
        # Look for existing doc to update
        sql_find = f"""
            SELECT id FROM {TABLA_DESTINO} 
            WHERE gestion=%s AND (nro_comprobante=%s OR nro_comprobante=%s) 
            AND (tipo_documento=%s OR tipo_documento_id=%s)
        """
        cursor.execute(sql_find, (gestion, comp_raw, comp_norm, TIPO_DOC_STR, TIPO_DOC_ID))
        doc = cursor.fetchone()
        
        if not doc:
            # Create ?? User said re-import. Usually implies update if exists or insert if missing.
            # But containers are "correctly imported". So docs MUST exist.
            # If not found, maybe I should insert index 'not found'.
            not_found += 1
            # print(f"Doc not found: {gestion}-{comp_norm}")
            continue
            
        doc_id = doc['id']
        
        # Prepare updates
        updates = []
        params = []
        
        # Enforce consistency requested by user
        updates.append("tipo_documento = %s")
        params.append(TIPO_DOC_STR)
        
        updates.append("tipo_documento_id = %s")
        params.append(TIPO_DOC_ID)
        
        if col_abc:
            abc_val = row.get(col_abc)
            if pd.notna(abc_val):
                updates.append("codigo_abc = %s")
                params.append(str(abc_val).strip())
                
        if col_obs:
            obs_val = row.get(col_obs)
            if pd.notna(obs_val):
                updates.append("observaciones = %s")
                params.append(str(obs_val).strip())
                
        # Extra: Capture all unknown cols into atributos_extra? 
        # For now, let's stick to core fields unless requested.
        
        if updates:
            sql_update = f"UPDATE {TABLA_DESTINO} SET {', '.join(updates)} WHERE id=%s"
            params.append(doc_id)
            cursor.execute(sql_update, tuple(params))
            updated += 1
            
    conn.commit()
    conn.close()
    print(f"Finalizado.")
    print(f"  Documentos actualizados: {updated}")
    print(f"  No encontrados: {not_found}")

if __name__ == "__main__":
    main()
