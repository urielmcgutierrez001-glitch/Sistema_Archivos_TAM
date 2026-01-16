
import pandas as pd
import os

base_path = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"
files = [
    "01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx",
    "02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx",
    "04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx",
    "05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx"
]

for f in files:
    path = os.path.join(base_path, f)
    print(f"\n--- {f} ---")
    try:
        df = pd.read_excel(path, nrows=0)
        cols = df.columns.tolist()
        potential = [c for c in cols if any(k in c.upper() for k in ['UBI', 'BLOQUE', 'NIVEL', 'LUGAR', 'ESTANTE', 'AREA'])]
        print("Potential Location Columns:", potential)
    except Exception as e:
        print(f"Error reading {f}: {e}")
