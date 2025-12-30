#!/usr/bin/env python3
"""
Analizar estructura de archivos Excel
Muestra las columnas de cada archivo para mapeo correcto
"""

import pandas as pd
import os

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

EXCEL_FILES = {
    'REGISTRO_DIARIO': '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'REGISTRO_INGRESO': '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'REGISTRO_CEPS': '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'PREVENTIVOS': '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'ASIENTOS_MANUALES': '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'DIARIOS_APERTURA': '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'REGISTRO_TRASPASO': '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    'HOJA_RUTA_DIARIOS': '08 HOJAS DE RUTA - DIARIOS TAMEP ARCHIVOS 2007 - 2026.xlsx'
}

print("="*80)
print("ANÁLISIS DE ESTRUCTURA DE ARCHIVOS EXCEL")
print("="*80)

for tipo_doc, archivo in EXCEL_FILES.items():
    ruta_completa = os.path.join(EXCEL_DIR, archivo)
    
    if not os.path.exists(ruta_completa):
        print(f"\n❌ {tipo_doc}: Archivo no encontrado")
        continue
    
    try:
        # Leer solo las primeras 5 filas para análisis
        df = pd.read_excel(ruta_completa, nrows=5)
        
        print(f"\n{'='*80}")
        print(f"📄 {tipo_doc}")
        print(f"   Archivo: {archivo}")
        print(f"   Total filas: {len(pd.read_excel(ruta_completa))}")
        print(f"{'='*80}")
        
        print("\n   COLUMNAS:")
        for i, col in enumerate(df.columns, 1):
            # Obtener tipo de datos y muestra
            tipo = df[col].dtype
            muestra = df[col].iloc[0] if not df[col].isna().all() else "N/A"
            print(f"   {i:2}. {col:30} | Tipo: {str(tipo):10} | Ejemplo: {str(muestra)[:30]}")
        
    except Exception as e:
        print(f"\n❌ Error al leer {tipo_doc}: {e}")

print("\n" + "="*80)
print("ANÁLISIS COMPLETADO")
print("="*80)
