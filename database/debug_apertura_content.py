
import pandas as pd
import os

path = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel\06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx"
df = pd.read_excel(path, nrows=10)
print("Columns:", df.columns.tolist())
# Print 'NRO. LIBRO\nAMARRO' specifically if it exists
found = next((c for c in df.columns if 'LIBRO' in c), None)
if found:
    print(f"Column '{found}' values:")
    print(df[found])
else:
    print("Column LIBRO not found")

print("\nFull head:")
print(df.head())
