#!/usr/bin/env python3
"""
Importar Observaciones desde Excel
==========================================
Lee la columna "OBSERVACIONES" de los archivos Excel
y actualiza el campo observaciones en la BD.
"""

import pandas as pd
import pymysql
import re
import os
from datetime import datetime

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
    ('REGISTRO_DIARIO', '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['DIARIO', 'COMPROBANTE']),
    ('REGISTRO_INGRESO', '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['INGRESO', 'COMPROBANTE']),
    ('REGISTRO_CEPS', '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['CEPS', 'COMPROBANTE', 'EGRESO']),
    ('PREVENTIVOS', '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['PREVENTIVO', 'COMPROBANTE']),
    ('ASIENTOS_MANUALES', '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx', ['MANUAL', 'ASIENTO', 'COMPROBANTE']),
    ('DIARIOS_APERTURA', '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx', ['APERTURA', 'TRASPASO', 'COMPROBANTE']),
    ('REGISTRO_TRASPASO', '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['TRASPASO', 'COMPROBANTE']),
]

def encontrar_columna(df, patrones, excluir_primera=True):
    columnas = list(df.columns)
    inicio = 1 if excluir_primera and len(columnas) > 1 else 0
    
    for col in columnas[inicio:]:
        col_limpio = col.strip().replace('\n', ' ').replace('  ', ' ').upper()
        for patron in patrones:
            if patron.upper() in col_limpio:
                return col
    return None

def normalizar_nro_comprobante(valor):
    if pd.isna(valor):
        return None
    
    valor_str = str(valor).strip()
    
    match = re.match(r'^[A-Za-z]-?0*(\d+)$', valor_str)
    if match:
        return match.group(1)
    
    match = re.match(r'^0*(\d+)$', valor_str)
    if match:
        return match.group(1)
    
    try:
        return str(int(float(valor_str)))
    except:
        return valor_str

def limpiar_observacion(valor):
    if pd.isna(valor):
        return None
    valor_str = str(valor).strip()
    if valor_str.lower() in ['nan', 'none', '', '0', '.']:
        return None
    return valor_str

print("="*80)
print("IMPORTACIÓN DE OBSERVACIONES DESDE EXCEL")
print("="*80)
print(f"Hora inicio: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

connection = pymysql.connect(**DB_CONFIG)
total_actualizados = 0

try:
    with connection.cursor() as cursor:
        for tipo_doc, archivo, patrones_comprobante in EXCEL_FILES:
            ruta = os.path.join(EXCEL_DIR, archivo)
            if not os.path.exists(ruta):
                print(f"\n⚠️  Archivo no encontrado: {archivo}")
                continue
            
            print(f"\n{'='*80}")
            print(f"📄 {tipo_doc}: {archivo}")
            print(f"{'='*80}")
            
            df = pd.read_excel(ruta)
            print(f"   📊 {len(df)} filas en Excel")
            
            # Obtener columnas necesarias
            col_gestion = encontrar_columna(df, ['GESTION', 'GESTIÓN'], excluir_primera=False)
            col_comprobante = encontrar_columna(df, patrones_comprobante, excluir_primera=True)
            col_observacion = encontrar_columna(df, ['OBSERVACIONES', 'OBS'], excluir_primera=False)
            
            if not col_gestion or not col_comprobante:
                print(f"   ⚠️  Columnas de gestión o comprobante no encontradas")
                cols_encontradas = [col for col in [col_gestion, col_comprobante] if col]
                print(f"      Encontradas: {cols_encontradas}")
                continue
                
            if not col_observacion:
                print(f"   ⚠️  Columna OBSERVACIONES no encontrada en este archivo")
                # List candidates
                posibles = [c for c in df.columns if 'OBS' in c.upper()]
                if posibles:
                    print(f"      Posibles candidatos: {posibles}")
                continue
            
            print(f"   📋 Columna Observaciones: {col_observacion}")
            
            actualizados_archivo = 0
            errores = 0
            
            for idx, fila in df.iterrows():
                try:
                    gestion_val = fila[col_gestion]
                    if pd.isna(gestion_val):
                        continue
                    
                    try:
                        gestion = int(float(gestion_val))
                    except:
                        continue
                    
                    comprobante_raw = fila[col_comprobante]
                    if pd.isna(comprobante_raw):
                        continue
                    
                    comprobante = normalizar_nro_comprobante(comprobante_raw)
                    if not comprobante:
                        continue
                    
                    observacion = limpiar_observacion(fila[col_observacion])
                    
                    if observacion:
                        # Actualizar en BD
                        # Solo actualizamos si la observación en BD está vacía o es diferente?
                        # Mejor sobrescribir para asegurar fidelidad con el Excel, o SOLO actualizar si es NULL?
                        # El usuario dijo "este campo esta vacio", así que asumiremos actualización.
                        
                        cursor.execute("""
                            UPDATE registro_diario
                            SET observaciones = %s
                            WHERE gestion = %s 
                            AND nro_comprobante = %s
                            AND tipo_documento = %s
                            AND (observaciones IS NULL OR observaciones = '')
                        """, (observacion, gestion, comprobante, tipo_doc))
                        
                        if cursor.rowcount > 0:
                            actualizados_archivo += cursor.rowcount
                
                except Exception as e:
                    errores += 1
                    if errores <= 3:
                        print(f"   ⚠️  Error fila {idx + 2}: {str(e)[:80]}")
                    continue
            
            connection.commit()
            print(f"   ✅ Observaciones actualizadas: {actualizados_archivo}")
            total_actualizados += actualizados_archivo
            
            if errores > 0:
                print(f"   ⚠️  Errores de fila: {errores}")

    print(f"\n{'='*80}")
    print(f"✅ FINALIZADO - Total documentos con nuevas observaciones: {total_actualizados}")
    print(f"{'='*80}")

except Exception as e:
    connection.rollback()
    print(f"\n❌ ERROR GLOBAL: {e}")
    import traceback
    traceback.print_exc()
finally:
    connection.close()

print(f"\n📊 Hora fin: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
