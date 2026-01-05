#!/usr/bin/env python3
"""
Importar Estados de Documentos desde Excel
==========================================
Lee columnas "Estado (Perdido)" y "OBSERVACIONES" de los archivos Excel
y actualiza los estados en la BD.

Estados detectados: FALTA, ANULADO, INUTILIZADO, PRESTADO
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

# Estados a detectar (en mayúsculas para comparación)
ESTADOS_BUSCAR = {
    'FALTA': 'FALTA',
    'ANULADO': 'ANULADO',
    'INUTILIZADO': 'NO UTILIZADO',
    'NO UTILIZADO': 'NO UTILIZADO',
    'NOUTILIZADO': 'NO UTILIZADO',
    'PRESTADO': 'PRESTADO',
}

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

def detectar_estado(fila, df):
    """
    Detecta estado del documento buscando en:
    - Estado (Perdido)
    - OBSERVACIONES
    """
    campos_buscar = ['Estado', 'Perdido', 'OBSERVACIONES', 'OBS']
    
    for col in df.columns:
        col_upper = col.upper().replace('\n', ' ')
        
        if any(campo.upper() in col_upper for campo in campos_buscar):
            valor = fila[col]
            if pd.isna(valor):
                continue
            
            valor_str = str(valor).upper().strip()
            
            # Buscar cada estado en el texto
            for buscar, estado in ESTADOS_BUSCAR.items():
                if buscar in valor_str:
                    return estado
    
    return None  # No se encontró estado especial

print("="*80)
print("IMPORTACIÓN DE ESTADOS DE DOCUMENTOS DESDE EXCEL")
print("="*80)
print(f"Hora inicio: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

connection = pymysql.connect(**DB_CONFIG)
total_actualizados = 0
estadisticas = {}

try:
    with connection.cursor() as cursor:
        # Ver estado actual
        print("\n📊 Estados actuales en BD:")
        cursor.execute("""
            SELECT estado_documento, COUNT(*) as c 
            FROM registro_diario 
            GROUP BY estado_documento 
            ORDER BY c DESC
        """)
        for row in cursor.fetchall():
            print(f"   {row['estado_documento'] or 'NULL'}: {row['c']}")
        
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
            
            if not col_gestion or not col_comprobante:
                print(f"   ⚠️  Columnas requeridas no encontradas")
                continue
            
            # Ver qué columnas de estado/observaciones existen
            cols_estado = []
            for col in df.columns:
                col_upper = col.upper().replace('\n', ' ')
                if 'ESTADO' in col_upper or 'PERDIDO' in col_upper or 'OBSERV' in col_upper:
                    cols_estado.append(col)
            
            print(f"   📋 Columnas de estado: {cols_estado}")
            
            actualizados = {estado: 0 for estado in set(ESTADOS_BUSCAR.values())}
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
                    
                    # Detectar estado
                    estado = detectar_estado(fila, df)
                    
                    if estado:
                        # Actualizar en BD
                        cursor.execute("""
                            UPDATE registro_diario
                            SET estado_documento = %s
                            WHERE gestion = %s 
                            AND nro_comprobante = %s
                            AND tipo_documento = %s
                            AND (estado_documento IS NULL OR estado_documento = 'DISPONIBLE')
                        """, (estado, gestion, comprobante, tipo_doc))
                        
                        if cursor.rowcount > 0:
                            actualizados[estado] += cursor.rowcount
                
                except Exception as e:
                    errores += 1
                    if errores <= 3:
                        print(f"   ⚠️  Error fila {idx + 2}: {str(e)[:80]}")
                    continue
            
            connection.commit()
            
            total_archivo = sum(actualizados.values())
            if total_archivo > 0:
                print(f"\n   ✅ Estados actualizados:")
                for estado, count in actualizados.items():
                    if count > 0:
                        print(f"      {estado}: {count}")
                        estadisticas[estado] = estadisticas.get(estado, 0) + count
                total_actualizados += total_archivo
            else:
                print(f"\n   ℹ️  Sin estados especiales encontrados")
            
            if errores > 0:
                print(f"   ⚠️  Errores: {errores}")
        
        # Estadísticas finales
        print(f"\n{'='*80}")
        print("📊 RESUMEN FINAL")
        print(f"{'='*80}")
        
        cursor.execute("""
            SELECT estado_documento, COUNT(*) as c 
            FROM registro_diario 
            GROUP BY estado_documento 
            ORDER BY c DESC
        """)
        print("\n📊 Estados en BD después de importación:")
        for row in cursor.fetchall():
            print(f"   {row['estado_documento'] or 'NULL'}: {row['c']}")
        
        print(f"\n✅ Total documentos actualizados: {total_actualizados}")
        if estadisticas:
            print("\n📈 Desglose:")
            for estado, count in estadisticas.items():
                print(f"   {estado}: {count}")

except Exception as e:
    connection.rollback()
    print(f"\n❌ ERROR: {e}")
    import traceback
    traceback.print_exc()
finally:
    connection.close()

print(f"\n📊 Hora fin: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
