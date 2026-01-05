#!/usr/bin/env python3
"""
Prueba de detección de columnas
"""
import pandas as pd
import os

def normalizar_columna(nombre_col):
    """Normaliza nombre de columna para búsqueda"""
    return nombre_col.strip().replace('\n', ' ').replace('  ', ' ').upper()

def buscar_columna_por_patron(df, palabras_clave):
    """Busca una columna que contenga TODAS las palabras clave"""
    for col in df.columns:
        col_norm = normalizar_columna(col)
        if all(palabra.upper() in col_norm for palabra in palabras_clave):
            return col
    return None

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

EXCEL_FILES = [
    ('REGISTRO_DIARIO', '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_INGRESO', '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_CEPS', '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('PREVENTIVOS', '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx'),
]

print("="*80)
print("PRUEBA DE DETECCIÓN DE COLUMNAS DE CONTENEDOR")
print("="*80)

for tipo, archivo in EXCEL_FILES:
    ruta = os.path.join(EXCEL_DIR, archivo)
    if not os.path.exists(ruta):
        continue
    
    df = pd.read_excel(ruta, nrows=1)
    
    col_contenedor = buscar_columna_por_patron(df, ['LIBRO', 'AMARR'])
    if not col_contenedor:
        col_contenedor = buscar_columna_por_patron(df, ['NRO', 'LIBRO'])
    
    col_bloque = buscar_columna_por_patron(df, ['BLOQUE'])
    col_color = buscar_columna_por_patron(df, ['LIBRO', 'COLOR'])
    
    print(f"\n📄 {tipo}")
    print(f"   Contenedor: {repr(col_contenedor)}")
    print(f"   Bloque: {repr(col_bloque)}")
    print(f"   Color: {repr(col_color)}")

print("\n" + "="*80)
