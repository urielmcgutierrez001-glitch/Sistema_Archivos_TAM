
import pandas as pd
import pymysql
import os

# Configuración de BD
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

BASE_PATH = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

# Archivos a escanear (Principalmente Apertura, pero podriamos ver otros)
ARCHIVOS = [
    "06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx"
]

def conectar_db():
    return pymysql.connect(**DB_CONFIG)

def get_ubicaciones_db(conn):
    with conn.cursor() as cursor:
        cursor.execute("SELECT id, nombre FROM ubicaciones")
        # Retorna dict {nombre_upper: id}
        return {row['nombre'].strip().upper(): row['id'] for row in cursor.fetchall()}

def main():
    print("Iniciando escaneo e inserción de NUEVAS ubicaciones...")
    conn = conectar_db()
    existing_locs = get_ubicaciones_db(conn)
    print(f"Ubicaciones existentes: {len(existing_locs)}")
    
    new_locs = set()
    
    for archivo in ARCHIVOS:
        ruta = os.path.join(BASE_PATH, archivo)
        print(f"Leyendo {archivo}...")
        try:
            df = pd.read_excel(ruta)
            # Buscar columna ubicacion
            col_ubi = next((c for c in df.columns if "UBICACIÓN" in c.upper() or "UNIDAD/ÁREA" in c.upper()), None)
            
            if col_ubi:
                unique_vals = df[col_ubi].dropna().unique()
                for val in unique_vals:
                    val_str = str(val).strip()
                    if val_str.upper() not in existing_locs:
                        new_locs.add(val_str)
            else:
                print(f"  Columna de ubicación no encontrada en {archivo}")
                
        except Exception as e:
            print(f"Error leyendo {archivo}: {e}")
            
    print(f"\nNuevas ubicaciones encontradas: {len(new_locs)}")
    for loc in new_locs:
        print(f"  - {loc}")
        
    if new_locs:
        print("\nInsertando en BD...")
        cursor = conn.cursor()
        count = 0
        for loc in new_locs:
            try:
                # Verificar de nuevo por si acaso (aunque set maneja unicos)
                if loc.upper() not in existing_locs:
                    cursor.execute("INSERT INTO ubicaciones (nombre, activo, fecha_creacion) VALUES (%s, 1, NOW())", (loc,))
                    existing_locs[loc.upper()] = cursor.lastrowid # Update local cache
                    count += 1
            except Exception as e:
                print(f"Error insertando {loc}: {e}")
        conn.commit()
        print(f"Insertadas {count} ubicaciones.")
    else:
        print("No hay ubicaciones nuevas para insertar.")
        
    conn.close()

if __name__ == "__main__":
    main()
