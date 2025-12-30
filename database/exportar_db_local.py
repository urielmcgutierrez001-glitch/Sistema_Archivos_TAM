#!/usr/bin/env python3
"""
Exportar base de datos local a SQL dump
Usa mysqldump para crear un archivo SQL que se puede importar a Clever Cloud
"""
import subprocess
import os
from datetime import datetime

DB_NAME = 'tamep_archivos'
OUTPUT_FILE = f'dump_tamep_{datetime.now().strftime("%Y%m%d_%H%M%S")}.sql'
OUTPUT_PATH = os.path.join(os.path.dirname(__file__), OUTPUT_FILE)

# Tablas a exportar (solo datos, no estructura completa)
TABLAS = [
    'contenedores_fisicos',
    'registro_diario',
    'registro_hojas_ruta',
    'clasificacion_contenedor_documento'
]

print("="*80)
print("EXPORTANDO BASE DE DATOS LOCAL")
print("="*80)
print(f"\nBase de datos: {DB_NAME}")
print(f"Archivo destino: {OUTPUT_FILE}")

try:
    # Verificar si mysqldump está disponible
    try:
        subprocess.run(['mysqldump', '--version'], capture_output=True, check=True)
        mysqldump_cmd = 'mysqldump'
    except:
        # Intentar con ruta completa común
        mysql_paths = [
            r'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
            r'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe',
            r'C:\xampp\mysql\bin\mysqldump.exe',
        ]
        mysqldump_cmd = None
        for path in mysql_paths:
            if os.path.exists(path):
                mysqldump_cmd = path
                break
        
        if not mysqldump_cmd:
            print("\n❌ No se encontró mysqldump")
            print("Por favor, usa Python para exportar:")
            print("   python exportar_con_python.py")
            exit(1)
    
    # Comando mysqldump
    # Solo exportar datos (--no-create-info) y las tablas especificadas
    cmd = [
        mysqldump_cmd,
        '-u', 'root',
        '--skip-password',
        '--no-create-info',  # No incluir CREATE TABLE
        '--compact',  # Formato compacto
        '--complete-insert',  # INSERT completos con nombres de columnas
        DB_NAME
    ] + TABLAS
    
    print(f"\n📤 Exportando datos...")
    
    result = subprocess.run(cmd, capture_output=True, text=True, encoding='utf-8')
    
    if result.returncode == 0:
        # Guardar el dump
        with open(OUTPUT_PATH, 'w', encoding='utf-8') as f:
            # Agregar header
            f.write(f"-- Dump de base de datos TAMEP\n")
            f.write(f"-- Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"-- Base de datos origen: {DB_NAME}\n")
            f.write(f"--\n\n")
            f.write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n")
            f.write("SET FOREIGN_KEY_CHECKS = 0;\n\n")
            f.write(result.stdout)
            f.write("\n\nSET FOREIGN_KEY_CHECKS = 1;\n")
        
        file_size = os.path.getsize(OUTPUT_PATH) / 1024 / 1024
        print(f"\n✅ Exportación completada")
        print(f"   Archivo: {OUTPUT_FILE}")
        print(f"   Tamaño: {file_size:.2f} MB")
        print(f"\n📋 Siguiente paso:")
        print(f"   python importar_a_clevercloud.py {OUTPUT_FILE}")
    else:
        print(f"\n❌ Error en mysqldump:")
        print(result.stderr)
        
except Exception as e:
    print(f"\n❌ Error: {e}")
    import traceback
    traceback.print_exc()

print("\n" + "="*80)
