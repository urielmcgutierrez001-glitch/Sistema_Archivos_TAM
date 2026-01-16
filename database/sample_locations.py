
import pandas as pd
import os

base_path = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"
files = [
    "01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx",
    "03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx"
]

for f in files:
    path = os.path.join(base_path, f)
    print(f"\n--- {f} ---")
    try:
        df = pd.read_excel(path, usecols=lambda x: x and any(k in x.upper() for k in ['UBI', 'AREA', 'BLOQUE', 'NIVEL']))
        for col in df.columns:
            print(f"Columna: {col}")
            print(df[col].dropna().unique()[:20]) # Show first 20 unique values
    except Exception as e:
        print(f"Error reading {f}: {e}")
