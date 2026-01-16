
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
ARCHIVO = "06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx"
TIPO_DOC = "DIARIOS_APERTURA"
COL_COMPROBANTE_PATRON = "COMPROBANTE"
COL_CONTENEDOR_PATRON = ["LIBRO", "AMARR"]

# Mapeo de ubicaciones (mismo que antes)
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

def get_ubicacion_id(nombre):
    if pd.isna(nombre): return None
    nombre = str(nombre).strip().upper()
    for key, val in UBICACIONES_MAP.items():
        if key in nombre: return val
    return None

def main():
    print(f"Iniciando REPARACION de contenedores para {TIPO_DOC}...")
    conn = conectar_db()
    cursor = conn.cursor()
    
    ruta = os.path.join(BASE_PATH, ARCHIVO)
    df = pd.read_excel(ruta)
    
    col_comp = encontrar_columna(df, COL_COMPROBANTE_PATRON)
    col_cont = encontrar_columna(df, COL_CONTENEDOR_PATRON)
    col_ubi = encontrar_columna(df, ["UBI", "AREA"])
    col_bloque = encontrar_columna(df, ["BLOQUE", "NIVEL"])
    col_color = encontrar_columna(df, ["COLOR"])
    
    print(f"Columnas: Comp={col_comp}, Cont={col_cont}, Ubi={col_ubi}, Bloque={col_bloque}")
    
    # Cache manual de ubicaciones si needed, pero usaremos el MAP statico
    
    # Cache contenedores existing
    contenedores_cache = {} # key: f"{tipo}-{numero}-{TIPO_DOC}" -> id
    
    created = 0
    updated = 0
    errors = 0
    
    for index, row in df.iterrows():
        gestion = row.get('GESTION')
        if pd.isna(gestion): 
            print(f"Row {index}: NaN gestion")
            continue
        
        comp_raw = row.get(col_comp)
        comp_norm = normalizar_nro_comprobante(comp_raw)
        if not comp_norm: 
            print(f"Row {index}: Bad comp {comp_raw}")
            continue
        
        # 1. Find Doc
        sql_find = "SELECT id, contenedor_fisico_id FROM registro_diario WHERE gestion=%s AND (nro_comprobante=%s OR nro_comprobante=%s) AND tipo_documento=%s"
        cursor.execute(sql_find, (gestion, comp_raw, comp_norm, TIPO_DOC))
        doc = cursor.fetchone()
        
        if not doc:
            print(f"Row {index}: Doc not found in DB {gestion}-{comp_norm}")
            continue
            
        conten_id = doc['contenedor_fisico_id']
        
        # Datos del contenedor
        cont_num_raw = row.get(col_cont)
        cont_num = normalizar_numero_contenedor(cont_num_raw)
        
        if not cont_num:
            print(f"Row {index}: Invalid container num '{cont_num_raw}'")
            continue
            
        # Determinar tipo (default LIBRO, si color existe o columna dice libro)
        tipo_cont = 'LIBRO' # Default
        if col_cont and 'AMARRO' in col_cont.upper() and not ('LIBRO' in col_cont.upper()):
             tipo_cont = 'AMARRO' # Logic weak here, but usually 'LIBRO' is safe default or check color
        
        # Crear o buscar contenedor
        clave = f"{tipo_cont}-{cont_num}-{TIPO_DOC}"
        
        if clave in contenedores_cache:
            new_conten_id = contenedores_cache[clave]
            # print(f"Row {index}: Using cached container {new_conten_id}")
        else:
            # Buscar en BD
            sql_cont = "SELECT id FROM contenedores_fisicos WHERE tipo_contenedor=%s AND numero=%s AND tipo_documento=%s"
            cursor.execute(sql_cont, (tipo_cont, cont_num, TIPO_DOC))
            res_cont = cursor.fetchone()
            
            if res_cont:
                new_conten_id = res_cont['id']
                # print(f"Row {index}: Found existing container {new_conten_id}")
            else:
                # CREAR
                print(f"Row {index}: Creating container {clave}")
                bloque = row.get(col_bloque)
                bloque = str(bloque).strip() if pd.notna(bloque) else None
                
                ubi_raw = row.get(col_ubi)
                ubi_id = get_ubicacion_id(ubi_raw)
                
                color = row.get(col_color)
                color = str(color).strip() if pd.notna(color) else None

                sql_ins = "INSERT INTO contenedores_fisicos (tipo_contenedor, tipo_documento, numero, bloque_nivel, color, ubicacion_id, activo) VALUES (%s, %s, %s, %s, %s, %s, 1)"
                cursor.execute(sql_ins, (tipo_cont, TIPO_DOC, cont_num, bloque, color, ubi_id))
                new_conten_id = cursor.lastrowid
                created += 1
            
            contenedores_cache[clave] = new_conten_id
            
        # Link Doc if different
        if conten_id != new_conten_id:
            print(f"Row {index}: Updating doc {doc['id']} from cont {conten_id} to {new_conten_id}")
            cursor.execute("UPDATE registro_diario SET contenedor_fisico_id=%s WHERE id=%s", (new_conten_id, doc['id']))
            updated += 1
            
    conn.commit()
    conn.close()
    print(f"Finalizado. Contenedores creados: {created}. Documentos actualizados/linkeados: {updated}")

if __name__ == "__main__":
    main()
