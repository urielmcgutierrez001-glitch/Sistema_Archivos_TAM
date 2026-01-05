#!/usr/bin/env python3
"""
Importador de Excel a MySQL Local - Sistema TAMEP
==================================================
Importa documentos desde Excel a la BD local (localhost)
Incluye todos los tipos de documentos incluyendo DIARIOS_APERTURA
"""

import pandas as pd
import pymysql
import re
import os
from datetime import datetime

# =====================================================
# CONFIGURACIÓN DE BASE DE DATOS LOCAL
# =====================================================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

# =====================================================
# RUTAS DE ARCHIVOS EXCEL
# =====================================================
EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

# Todos los archivos Excel incluyendo DIARIOS_APERTURA
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
    """Busca columna que coincida con algún patrón"""
    columnas = list(df.columns)
    inicio = 1 if excluir_primera and len(columnas) > 1 else 0
    
    for col in columnas[inicio:]:
        col_limpio = col.strip().replace('\n', ' ').replace('  ', ' ').upper()
        for patron in patrones:
            if patron.upper() in col_limpio:
                return col
    return None

def expandir_rango(valor):
    """Expande rangos como '12-20' en [12, 13, ..., 20]"""
    if pd.isna(valor):
        return []
    
    valor_str = str(valor).strip()
    match = re.match(r'^(\d+)\s*-\s*(\d+)$', valor_str)
    
    if match:
        inicio = int(match.group(1))
        fin = int(match.group(2))
        return list(range(inicio, fin + 1))
    else:
        # Normalizar formatos como A-00001 → 1
        normalized = normalizar_nro_comprobante(valor_str)
        try:
            return [int(float(normalized))]
        except:
            return [normalized] if normalized else []

def normalizar_nro_comprobante(valor):
    """
    Normaliza número de comprobante:
    A-00001 → 1
    A-00020 → 20
    00005 → 5
    5 → 5
    """
    if not valor:
        return None
    
    valor_str = str(valor).strip()
    
    # Patrón A-NNNNN
    match = re.match(r'^[A-Za-z]-?0*(\d+)$', valor_str)
    if match:
        return match.group(1)
    
    # Patrón solo números con ceros a la izquierda
    match = re.match(r'^0*(\d+)$', valor_str)
    if match:
        return match.group(1)
    
    # Intentar convertir directamente
    try:
        return str(int(float(valor_str)))
    except:
        return valor_str

def limpiar_valor(valor):
    if pd.isna(valor):
        return None
    if isinstance(valor, str) and valor.strip().lower() in ['nan', 's/n', '', 'n/a']:
        return None
    return str(valor).strip() if isinstance(valor, str) else valor

def detectar_estado(fila, df):
    """Detecta estado del documento"""
    estados = ['ANULADO', 'INUTILIZADO', 'FALTA', 'PRESTADO']
    campos = ['Estado', 'OBSERVACIONES', 'OBS']
    
    for col in df.columns:
        col_upper = col.upper()
        if any(c.upper() in col_upper for c in campos):
            valor = fila[col]
            if pd.isna(valor):
                continue
            valor_str = str(valor).upper()
            for estado in estados:
                if estado in valor_str:
                    return estado
    return 'DISPONIBLE'

print("="*80)
print("IMPORTACIÓN DE EXCEL A BD LOCAL - TAMEP")
print("="*80)
print(f"Hora inicio: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

connection = pymysql.connect(**DB_CONFIG)
total_general = 0
total_nuevos = 0

try:
    with connection.cursor() as cursor:
        # Verificar tipos de documento existentes
        print("\n📋 Verificando tipos de documento...")
        cursor.execute("SELECT tipo_documento, COUNT(*) as c FROM registro_diario GROUP BY tipo_documento")
        existentes = {row['tipo_documento']: int(row['c']) for row in cursor.fetchall()}
        print(f"   Tipos existentes: {list(existentes.keys())}")
        
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
            print(f"   📊 {existentes.get(tipo_doc, 0)} registros en BD")
            
            # Obtener columnas
            col_gestion = encontrar_columna(df, ['GESTION', 'GESTIÓN'], excluir_primera=False)
            col_comprobante = encontrar_columna(df, patrones_comprobante, excluir_primera=True)
            col_abc = encontrar_columna(df, ['ABC'], excluir_primera=True)
            
            print(f"   📋 Columnas: Gestión={col_gestion}, Comprobante={col_comprobante}")
            
            if not col_gestion or not col_comprobante:
                print(f"   ⚠️  Columnas requeridas no encontradas, saltando...")
                continue
            
            insertados = 0
            duplicados = 0
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
                    
                    comprobantes = expandir_rango(fila[col_comprobante])
                    if not comprobantes:
                        continue
                    
                    abc = limpiar_valor(fila[col_abc]) if col_abc else None
                    estado = detectar_estado(fila, df)
                    
                    for nro_comp in comprobantes:
                        comp_str = str(nro_comp)
                        
                        # Verificar si existe
                        cursor.execute("""
                            SELECT id FROM registro_diario 
                            WHERE gestion = %s AND nro_comprobante = %s AND tipo_documento = %s
                        """, (gestion, comp_str, tipo_doc))
                        
                        if cursor.fetchone():
                            duplicados += 1
                            continue
                        
                        # Insertar nuevo
                        cursor.execute("""
                            INSERT INTO registro_diario 
                            (gestion, nro_comprobante, codigo_abc, tipo_documento, estado_documento, activo)
                            VALUES (%s, %s, %s, %s, %s, 1)
                        """, (gestion, comp_str, abc, tipo_doc, estado))
                        insertados += 1
                
                except Exception as e:
                    errores += 1
                    if errores <= 3:
                        print(f"   ⚠️  Error fila {idx + 2}: {str(e)[:80]}")
                    continue
            
            connection.commit()
            total_general += insertados + duplicados
            total_nuevos += insertados
            
            print(f"\n   ✅ Nuevos insertados: {insertados}")
            print(f"   ⏭️  Ya existían: {duplicados}")
            if errores > 0:
                print(f"   ⚠️  Errores: {errores}")
        
        # Estadísticas finales
        cursor.execute("SELECT tipo_documento, COUNT(*) as c FROM registro_diario GROUP BY tipo_documento ORDER BY tipo_documento")
        print(f"\n{'='*80}")
        print("📊 ESTADO FINAL DE LA BASE DE DATOS")
        print(f"{'='*80}")
        total_bd = 0
        for row in cursor.fetchall():
            print(f"   {row['tipo_documento']}: {row['c']} registros")
            total_bd += int(row['c'])
        print(f"\n   TOTAL: {total_bd} registros")
        
except Exception as e:
    connection.rollback()
    print(f"\n❌ ERROR: {e}")
    import traceback
    traceback.print_exc()
finally:
    connection.close()

print(f"\n{'='*80}")
print(f"✅ IMPORTACIÓN COMPLETADA")
print(f"   Nuevos registros insertados: {total_nuevos}")
print(f"   Hora fin: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
print(f"{'='*80}")
print("\n📊 Ahora ejecuta 'python reimportar_contenedores_v5.py' para asignar contenedores")
