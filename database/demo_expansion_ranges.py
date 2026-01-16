import pandas as pd
import os
import re

EXCEL_FILE = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel\01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx"

def expandir_rango(valor):
    if pd.isna(valor): return []
    valor_str = str(valor).strip()
    match = re.match(r'^(\d+)\s*-\s*(\d+)$', valor_str)
    if match:
        inicio = int(match.group(1))
        fin = int(match.group(2))
        return list(range(inicio, fin + 1))
    return [valor]

print(f"Analizando {os.path.basename(EXCEL_FILE)}...")
df = pd.read_excel(EXCEL_FILE)

# Buscar columna de comprobante
col_comprobante = None
for col in df.columns:
    if "COMPROBANTE" in str(col).upper():
        col_comprobante = col
        break

if col_comprobante:
    print(f"Columna de comprobante detectada: {col_comprobante}")
    count_rangos = 0
    records_extra = 0
    
    print("\n--- EJEMPLOS DE EXPANSIÓN ---")
    for idx, row in df.iterrows():
        val = row[col_comprobante]
        if pd.isna(val): continue
        
        val_str = str(val).strip()
        if "-" in val_str and re.match(r'^\d+\s*-\s*\d+$', val_str):
            expandido = expandir_rango(val)
            cantidad = len(expandido)
            if cantidad > 1:
                count_rangos += 1
                records_extra += (cantidad - 1)
                if count_rangos <= 5: # Mostrar solo los primeros 5 ejemplos
                    print(f"Fila Excel {idx+2}: '{val_str}'  ---> Se convierte en {cantidad} registros: {expandido}")
    
    print(f"\nTotal filas con rangos: {count_rangos}")
    print(f"Total registros adicionales generados: {records_extra}")
    print(f"Total esperado (Filas Excel + Adicionales): {len(df) + records_extra}")
else:
    print("No se encontró columna COMPROBANTE")
