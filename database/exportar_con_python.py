#!/usr/bin/env python3
"""
Exportar base de datos usando Python (sin mysqldump)
Genera un archivo SQL con todos los INSERT statements
"""
import pymysql
import os
from datetime import datetime

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

OUTPUT_FILE = f'dump_tamep_{datetime.now().strftime("%Y%m%d_%H%M%S")}.sql'
OUTPUT_PATH = os.path.join(os.path.dirname(__file__), OUTPUT_FILE)

# Orden de exportación (respetando foreign keys)
TABLAS = [
    'contenedores_fisicos',
    'registro_diario',
    'registro_hojas_ruta',
    'clasificacion_contenedor_documento'
]

print("="*80)
print("EXPORTANDO BASE DE DATOS CON PYTHON")
print("="*80)

try:
    connection = pymysql.connect(**DB_CONFIG)
    
    with open(OUTPUT_PATH, 'w', encoding='utf-8') as f:
        # Header
        f.write(f"-- Dump de base de datos TAMEP\n")
        f.write(f"-- Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write(f"-- Herramienta: Python pymysql\n")
        f.write(f"--\n\n")
        f.write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n")
        f.write("SET FOREIGN_KEY_CHECKS = 0;\n")
        f.write("SET AUTOCOMMIT = 0;\n")
        f.write("START TRANSACTION;\n\n")
        
        with connection.cursor() as cursor:
            for tabla in TABLAS:
                print(f"\n📤 Exportando tabla: {tabla}")
                
                # Obtener datos
                cursor.execute(f"SELECT * FROM {tabla}")
                rows = cursor.fetchall()
                
                if not rows:
                    print(f"   ⚠️  Tabla vacía")
                    continue
                
                print(f"   📊 {len(rows):,} registros")
                
                f.write(f"\n-- Tabla: {tabla}\n")
                f.write(f"-- Registros: {len(rows)}\n\n")
                
                # Obtener nombres de columnas
                cursor.execute(f"SHOW COLUMNS FROM {tabla}")
                columns = [col['Field'] for col in cursor.fetchall()]
                
                # Generar INSERT statements (en lotes de 100)
                batch_size = 100
                for i in range(0, len(rows), batch_size):
                    batch = rows[i:i+batch_size]
                    
                    f.write(f"INSERT INTO `{tabla}` (`{'`, `'.join(columns)}`) VALUES\n")
                    
                    for idx, row in enumerate(batch):
                        values = []
                        for col in columns:
                            val = row[col]
                            if val is None:
                                values.append('NULL')
                            elif isinstance(val, (int, float)):
                                values.append(str(val))
                            elif isinstance(val, datetime):
                                values.append(f"'{val.strftime('%Y-%m-%d %H:%M:%S')}'")
                            else:
                                # Escapar comillas simples
                                val_str = str(val).replace("\\", "\\\\").replace("'", "\\'")
                                values.append(f"'{val_str}'")
                        
                        if idx < len(batch) - 1:
                            f.write(f"({', '.join(values)}),\n")
                        else:
                            f.write(f"({', '.join(values)});\n")
                
                print(f"   ✅ Completado")
        
        f.write("\n\nCOMMIT;\n")
        f.write("SET FOREIGN_KEY_CHECKS = 1;\n")
    
    file_size = os.path.getsize(OUTPUT_PATH) / 1024 / 1024
    print(f"\n{'='*80}")
    print(f"✅ EXPORTACIÓN COMPLETADA")
    print(f"{'='*80}")
    print(f"Archivo: {OUTPUT_FILE}")
    print(f"Tamaño: {file_size:.2f} MB")
    print(f"\n📋 Siguiente paso:")
    print(f"   python importar_a_clevercloud.py")
    
except Exception as e:
    print(f"\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
finally:
    if 'connection' in locals():
        connection.close()
