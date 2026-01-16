
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
ARCHIVO = "03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx"
TABLA_DOCS = "documentos"
TIPO_DOC_STR = "REGISTRO_CEPS" # Enum value in DB
TIPO_DOC_ID = 3

def conectar_db():
    return pymysql.connect(**DB_CONFIG)

def encontrar_columna(df, patrones):
    for col in df.columns:
        col_limpio = str(col).strip().upper().replace('\n', ' ')
        if isinstance(patrones, list):
            for p in patrones:
                if p in col_limpio: return col
        else:
            if patrones in col_limpio: return col
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

def main():
    print(f"Iniciando RE-IMPORTACION de CONTENEDORES para CEPS...")
    conn = conectar_db()
    cursor = conn.cursor()
    
    ruta = os.path.join(BASE_PATH, ARCHIVO)
    # Header logic: user script v5 used 'encontrar_columna' which implies scanning. 
    # But previous debug showed header might be offset. v5 used finding cols. 
    # Let's trust pd.read_excel default or try header=3 if standard.
    # Actually v5 didn't specify header offset, suggesting standard read works OR v5 had robust col finding.
    # Previous debug showed row 0 was junk. Let's try header=3 based on common Excel dumps or just scan.
    # Edit: Debug showed headers at roughly line 4-5. Let's use header=3 to be safe or inspect.
    # Wait, v5 just did read_excel(ruta) and scanned. I'll stick to that.
    
    df = pd.read_excel(ruta)
    
    # Try to locate the header row by finding "COMPROBANTE"
    header_idx = -1
    for idx, row in df.iterrows():
        row_str = " ".join([str(x) for x in row.values]).upper()
        if "COMPROBANTE" in row_str and "GESTION" in row_str:
            header_idx = idx + 1 # iterrows is 0-indexed, but this is skipping junk
            break
            
    if header_idx > 0:
        print(f"  Detectado encabezado en fila {header_idx} (0-based approx). Recargando...")
        df = pd.read_excel(ruta, header=header_idx) # header param is 0-indexed row number
    
    col_comp = encontrar_columna(df, "COMPROBANTE")
    col_gestion = encontrar_columna(df, "GESTION")
    col_cont = encontrar_columna(df, ["LIBRO", "AMARR"])
    col_color = encontrar_columna(df, "COLOR")
    col_bloque = encontrar_columna(df, ["BLOQUE", "NIVEL"])
    col_ubi = encontrar_columna(df, ["UBI", "AREA"])
    
    print(f"Cols: Comp={col_comp}, Gest={col_gestion}, Cont={col_cont}, Color={col_color}")
    
    if not col_comp or not col_cont:
        print("Error: No se encontraron columnas criticas.")
        return

    procesados = 0
    actualizados = 0
    creados = 0
    
    contenedores_cache = {}
    
    for _, row in df.iterrows():
        gestion = row.get(col_gestion)
        if pd.isna(gestion): continue
        
        comp_raw = row.get(col_comp)
        comp_norm = normalizar_nro_comprobante(comp_raw)
        if not comp_norm: continue
        
        procesados += 1
        
        # 1. Datos Contenedor
        cont_num_raw = row.get(col_cont)
        cont_num = normalizar_numero_contenedor(cont_num_raw)
        
        if not cont_num:
            continue
            
        # Regla: SI tiene COLOR -> LIBRO, else -> AMARRO
        color_val = row.get(col_color)
        tiene_color = pd.notna(color_val) and str(color_val).strip() != ''
        
        # Also check if col header implies it
        tipo_cont = 'AMARRO'
        if tiene_color:
            tipo_cont = 'LIBRO'
        # Fallback logic from v5: if col_cont has 'LIBRO' maybe? 
        # But User explicit rule: "si un contenedor tiene algun COLOR LIBRO llenado entonces es un LIBRO, si no es un AMARRO"
        
        # Build Container Key
        clave_cont = f"{tipo_cont}-{cont_num}-{TIPO_DOC_STR}"
        
        if clave_cont in contenedores_cache:
            conten_id = contenedores_cache[clave_cont]
        else:
            # Check DB
            sql_check = "SELECT id FROM contenedores_fisicos WHERE tipo_contenedor=%s AND numero=%s AND tipo_documento=%s"
            cursor.execute(sql_check, (tipo_cont, cont_num, TIPO_DOC_STR))
            res = cursor.fetchone()
            
            if res:
                conten_id = res['id']
            else:
                # Create
                # Grab extra info if available
                bloque = str(row.get(col_bloque)).strip() if col_bloque and pd.notna(row.get(col_bloque)) else None
                ubi_raw = row.get(col_ubi) # Maps to ID if needed, skipping for now or default ID?
                # v5 did mapping. Assuming standard Ubi ID if important, or let user fill later.
                # Actually import from previous steps dealt with locations. Let's try to map if simple.
                ubi_id = None # Should import standard map if needed.
                
                # INSERT
                sql_ins = """INSERT INTO contenedores_fisicos 
                             (tipo_contenedor, tipo_documento, numero, color, bloque_nivel, activo) 
                             VALUES (%s, %s, %s, %s, %s, 1)"""
                cursor.execute(sql_ins, (
                    tipo_cont, 
                    TIPO_DOC_STR, 
                    cont_num, 
                    str(color_val).strip() if tiene_color else None,
                    bloque
                ))
                conten_id = cursor.lastrowid
                creados += 1
                
            contenedores_cache[clave_cont] = conten_id
            
        # 2. Link Document
        # Match by Gestion + Comprobante + Tipo Doc
        # Note: 'documentos' table now. `tipo_documento` enum matching `TIPO_DOC_STR`.
        
        sql_doc = f"SELECT id, contenedor_fisico_id FROM {TABLA_DOCS} WHERE gestion=%s AND (nro_comprobante=%s OR nro_comprobante=%s) AND tipo_documento=%s"
        cursor.execute(sql_doc, (gestion, comp_raw, comp_norm, TIPO_DOC_STR))
        docs = cursor.fetchall()
        
        for doc in docs:
            if doc['contenedor_fisico_id'] != conten_id:
                cursor.execute(f"UPDATE {TABLA_DOCS} SET contenedor_fisico_id=%s WHERE id=%s", (conten_id, doc['id']))
                actualizados += 1

    conn.commit()
    conn.close()
    
    print(f"Finalizado.")
    print(f"  Filas procesadas: {procesados}")
    print(f"  Contenedores creados: {creados}")
    print(f"  Documentos vinculados/actualizados: {actualizados}")

if __name__ == "__main__":
    main()
