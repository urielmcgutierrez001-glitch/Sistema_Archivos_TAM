import mysql.connector

# --- CONFIGURACIÓN ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos'
}

# CONFIGURAR AQUÍ QUÉ QUIERES SINCRONIZAR
TABLA = 'documentos'                # Nombre de la tabla
COLUMNA_A_SYNC = 'contenedor_fisico_id' # Columna con el valor correcto (LOCAL)
COLUMNA_ID = 'id'                   # Clave primaria para identificar el registro
ARCHIVO_SALIDA = 'script_sync_documentos_nube.sql'
LIMITE_ID = 75825                   # Sincronizar solo hasta este ID

def generar_script():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        print(f"Conectando a BD local: {DB_CONFIG['database']}...")
        
        # Obtenemos los datos locales con límite
        query = f"SELECT {COLUMNA_ID}, {COLUMNA_A_SYNC} FROM {TABLA} WHERE {COLUMNA_ID} <= {LIMITE_ID} ORDER BY {COLUMNA_ID} ASC"
        cursor.execute(query)
        resultados = cursor.fetchall()
        
        print(f"Generando script para {len(resultados)} registros (IDs 1 a {LIMITE_ID})...")

        with open(ARCHIVO_SALIDA, 'w', encoding='utf-8') as f:
            f.write(f"-- Script de Sincronizacion 'documentos' Local -> Nube (Limitado a ID {LIMITE_ID})\n")
            f.write("SET SQL_SAFE_UPDATES = 0;\n\n")
            
            # Usamos transacciones para mayor velocidad en lotes grandes
            f.write("START TRANSACTION;\n")
            
            count = 0
            for row in resultados:
                id_val = row[COLUMNA_ID]
                valor = row[COLUMNA_A_SYNC]
                
                if valor is None:
                    sql = f"UPDATE {TABLA} SET {COLUMNA_A_SYNC} = NULL WHERE {COLUMNA_ID} = {id_val};\n"
                else:
                    # No necesitamos escapar mucho si es ID numérico, pero por seguridad
                    sql = f"UPDATE {TABLA} SET {COLUMNA_A_SYNC} = {valor} WHERE {COLUMNA_ID} = {id_val};\n"
                
                f.write(sql)
                
                # Commit cada 1000 registros para no saturar memoria si se corre manual
                count += 1
                if count % 1000 == 0:
                    f.write("COMMIT; START TRANSACTION;\n")
            
            f.write("COMMIT;\n")
            f.write("\nSET SQL_SAFE_UPDATES = 1;\n")

        print(f"¡Listo! Archivo generado: {ARCHIVO_SALIDA}")
        print("Ahora ejecuta este archivo en tu base de datos en la nube.")

    except mysql.connector.Error as err:
        print(f"Error: {err}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    generar_script()
