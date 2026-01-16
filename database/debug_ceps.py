
import pandas as pd
import os

path = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel\03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx"
df = pd.read_excel(path, nrows=5)
print("Columns:", df.columns.tolist())
print(df.head())
for col in df.columns:
    print(f"'{col}'")
