#!/usr/bin/env python3
"""
Script para ACTUALIZAR los contenedores existentes con datos faltantes
Agrega bloque_nivel y color desde los Excel
Maneja formato L-1 para libros
"""

import pandas as pd
import pymysql
import re
import os

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

EXCEL_FILES = [
    '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx',
]

def normalizar_numero_contenedor(valor):
    """
    Normaliza el número de contenedor
    L-1 → 1 (libro 1)
    L -1 → 1
    5.0 → 5
    """
    if pd.isna(valor):
        return None
    
    valor_str = str(valor).strip()
    
    # Patrón para L-N o L -N o L- N
    patron_libro = r'^L\s*-?\s*(\d+)$'
    match = re.match(patron_libro, valor_str, re.IGNORECASE)
    if match:
        return match.group(1)
    
    # Convertir a entero si es posible
    try:
        return str(int(float(valor_str)))
    except:
        return valor_str

def limpiar_valor(valor):
    """Limpia valores NaN"""
    if pd.isna(valor):
        return None
    if isinstance(valor, str) and valor.strip().lower() in ['nan', 's/n', '']:
        return None
    # Convertir a string si es número
    if isinstance(valor, (int, float)):
        return str(int(valor)) if valor == int(valor) else str(valor)
    return str(valor).strip()

def obtener_columna(df, nombres_posibles):
    """Busca columna por nombre"""
    for col in df.columns:
        col_norm = col.strip().replace('\n', ' ')
        if col_norm in nombres_posibles or col in nombres_posibles:
            return col
    return None

print("="*80)
print("ACTUALIZANDO CONTENEDORES CON DATOS DEL EXCEL")
print("="*80)

connection = pymysql.connect(**DB_CONFIG)
contenedores_actualizados = {}

try:
    with connection.cursor() as cursor:
        for archivo in EXCEL_FILES:
            ruta = os.path.join(EXCEL_DIR, archivo)
            if not os.path.exists(ruta):
                continue
            
            print(f"\n📄 Procesando: {archivo}")
            df = pd.read_excel(ruta)
            
            # Encontrar columnas
            col_numero = obtener_columna(df, [
                'NRO. LIBRO/AMARR', 'NRO. LIBRO AMARR', 
                'NRO. LIBRO\nAMARR', 'NRO. LIBRO\nAMARRO'
            ])
            col_bloque = obtener_columna(df, [
                'BLOQUE/NIVEL', 'BLOQUE / NIVEL', 'BLOQUE\nNIVEL'
            ])
            col_color = obtener_columna(df, [
                'LIBRO COLOR', 'LIBRO\nCOLOR'
            ])
            col_ubicacion = obtener_columna(df, [
                'Ubicación Unidad/Área'
            ])
            
            if not col_numero:
                print(f"   ⚠️  No se encontró columna de número")
                continue
            
            for idx, fila in df.iterrows():
                numero_raw = fila[col_numero]
                if pd.isna(numero_raw):
                    continue
                
                numero = normalizar_numero_contenedor(numero_raw)
                if not numero:
                    continue
                
                # Obtener datos
                bloque = limpiar_valor(fila[col_bloque]) if col_bloque else None
                color = limpiar_valor(fila[col_color]) if col_color else None
                ubicacion_nombre = limpiar_valor(fila[col_ubicacion]) if col_ubicacion else None
                
                # Determinar tipo
                tipo = 'LIBRO' if color else 'AMARRO'
                
                # Clave única para este contenedor
                clave = f"{tipo}-{numero}"
                
                # Acumular datos (tomar el último que tenga valores)
                if clave not in contenedores_actualizados:
                    contenedores_actualizados[clave] = {
                        'tipo': tipo,
                        'numero': numero,
                        'bloque': bloque,
                        'color': color,
                        'ubicacion': ubicacion_nombre
                    }
                else:
                    # Actualizar solo si hay valores nuevos
                    if bloque and not contenedores_actualizados[clave]['bloque']:
                        contenedores_actualizados[clave]['bloque'] = bloque
                    if color and not contenedores_actualizados[clave]['color']:
                        contenedores_actualizados[clave]['color'] = color
                    if ubicacion_nombre and not contenedores_actualizados[clave]['ubicacion']:
                        contenedores_actualizados[clave]['ubicacion'] = ubicacion_nombre
        
        # Actualizar contenedores en BD
        print(f"\n📝 Actualizando {len(contenedores_actualizados)} contenedores únicos...")
        actualizados = 0
        
        for clave, datos in contenedores_actualizados.items():
            # Buscar contenedor
            sql_buscar = """
                SELECT id, bloque_nivel, color, ubicacion_id 
                FROM contenedores_fisicos 
                WHERE tipo_contenedor = %s AND numero = %s
            """
            cursor.execute(sql_buscar, (datos['tipo'], datos['numero']))
            contenedor = cursor.fetchone()
            
            if not contenedor:
                # Crear si no existe
                ubicacion_id = None
                if datos['ubicacion']:
                    cursor.execute("SELECT id FROM ubicaciones WHERE nombre = %s", (datos['ubicacion'],))
                    ub = cursor.fetchone()
                    if ub:
                        ubicacion_id = ub['id']
                
                sql_crear = """
                    INSERT INTO contenedores_fisicos 
                    (tipo_contenedor, numero, bloque_nivel, color, ubicacion_id, activo)
                    VALUES (%s, %s, %s, %s, %s, 1)
                """
                cursor.execute(sql_crear, (
                    datos['tipo'], datos['numero'], 
                    datos['bloque'], datos['color'], ubicacion_id
                ))
                actualizados += 1
            else:
                # Actualizar solo campos vacíos
                updates = []
                params = []
                
                if datos['bloque'] and not contenedor['bloque_nivel']:
                    updates.append("bloque_nivel = %s")
                    params.append(datos['bloque'])
                
                if datos['color'] and not contenedor['color']:
                    updates.append("color = %s")
                    params.append(datos['color'])
                
                if datos['ubicacion'] and not contenedor['ubicacion_id']:
                    cursor.execute("SELECT id FROM ubicaciones WHERE nombre = %s", (datos['ubicacion'],))
                    ub = cursor.fetchone()
                    if ub:
                        updates.append("ubicacion_id = %s")
                        params.append(ub['id'])
                
                if updates:
                    sql_update = f"""
                        UPDATE contenedores_fisicos 
                        SET {', '.join(updates)}
                        WHERE id = %s
                    """
                    params.append(contenedor['id'])
                    cursor.execute(sql_update, tuple(params))
                    actualizados += 1
        
        connection.commit()
        print(f"✅ {actualizados} contenedores actualizados/creados")
        
except Exception as e:
    connection.rollback()
    print(f"❌ ERROR: {e}")
finally:
    connection.close()

print("\n📊 Ejecuta 'verificar_contenedores_actuales.sql' para ver los resultados")
