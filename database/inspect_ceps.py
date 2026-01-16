import pandas as pd
import os

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"
archivo = "06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx"
ruta = os.path.join(EXCEL_DIR, archivo)

print(f"Inspecting {archivo}...")
df = pd.read_excel(ruta, nrows=5)

for col in df.columns:
    print(f"Col: '{col}'")
    print(f" - Sample: {df[col].tolist()}")
