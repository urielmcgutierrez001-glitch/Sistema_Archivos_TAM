#!/usr/bin/env python3
"""
Analiza los archivos Excel para contar filas vs filas expandidas
"""
import pandas as pd
import os
import re

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

EXCEL_FILES = [
    ('REGISTRO_DIARIO', '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['DIARIO', 'COMPROBANTE']),
    ('REGISTRO_INGRESO', '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['INGRESO', 'COMPROBANTE']),
    ('REGISTRO_CEPS', '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['CEPS', 'COMPROBANTE', 'EGRESO', 'PREVENTIVOS', 'ASIENTOS', 'TRASPASO']),
    ('PREVENTIVOS', '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['PREVENTIVO', 'COMPROBANTE']),
    ('ASIENTOS_MANUALES', '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx', ['MANUAL', 'ASIENTO', 'COMPROBANTE']),
    ('DIARIOS_APERTURA', '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx', ['APERTURA', 'DIARIO', 'COMPROBANTE']),
    ('REGISTRO_TRASPASO', '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['TRASPASO', 'COMPROBANTE']),
    ('HOJA_RUTA_DIARIOS', '08 HOJAS DE RUTA - DIARIOS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['HOJA DE RUTA', 'RUTA'])
]

def encontrar_columna(df, patrones):
    columnas = list(df.columns)
    for col in columnas:
        col_limpio = str(col).strip().replace('\n', ' ').replace('  ', ' ')
        for patron in patrones:
            if patron.lower() in col_limpio.lower():
                return col
    return None

def contar_expandidos(valor):
    if pd.isna(valor):
        return 0
    valor_str = str(valor).strip()
    
    # Rango: "12-15"
    match_rango = re.match(r'^(\d+)\s*-\s*(\d+)$', valor_str)
    if match_rango:
        inicio = int(match_rango.group(1))
        fin = int(match_rango.group(2))
        return max(0, fin - inicio + 1)
    
    # Lista: "12, 14, 15"
    if ',' in valor_str:
        return len(valor_str.split(','))
        
    return 1

print("="*80)
print("ANÁLISIS DE CONTEO DE REGISTROS EXCEL")
print("="*80)

total_raw = 0
total_expanded = 0

for tipo_doc, archivo, patrones in EXCEL_FILES:
    ruta = os.path.join(EXCEL_DIR, archivo)
    if not os.path.exists(ruta):
        print(f"⚠️ Archivo no existe: {archivo}")
        continue
    
    # Solo necesitamos las columnas relevantes
    df = pd.read_excel(ruta)
    col_comprobante = encontrar_columna(df, patrones)
    
    rows = len(df)
    expanded = 0
    
    if col_comprobante:
        for val in df[col_comprobante]:
            if pd.isna(val):
                continue
            expanded += contar_expandidos(val)
    else:
        # Si no hay columna comprobante, asumimos 1 a 1 pero verificamos si es Hojas de Ruta
        # En Hojas de Ruta puede haber duplicados reales
        expanded = rows

    print(f"\n📄 {tipo_doc}:")
    print(f"   Filas Raw: {rows}")
    print(f"   Filas Expandidas (aprox): {expanded}")
    print(f"   Columna detectada: {col_comprobante}")
    
    total_raw += rows
    total_expanded += expanded

print("="*80)
print(f"TOTAL FILAS RAW: {total_raw}")
print(f"TOTAL EXPANDIDO EXPECTED (aprox): {total_expanded}")
print("="*80)
