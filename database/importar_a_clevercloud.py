#!/usr/bin/env python3
"""
Importar dump SQL a Clever Cloud MySQL
"""
import pymysql
import os
import sys
from datetime import datetime

DB_CONFIG = {
    'host': 'bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com',
    'user': 'uh5uxh0yxbs9cxva',
    'password': 'HdTIK6C8X5M5qsQUTXoE',
    'database': 'bf7yz05jw1xmnb2vukrs',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

# Buscar el archivo dump más reciente
import glob
dump_files = glob.glob('dump_tamep_*.sql')
if not dump_files:
    print("❌ No se encontró ningún archivo dump_tamep_*.sql")
    sys.exit(1)

SQL_FILE = sorted(dump_files)[-1]  # Más reciente

print("="*80)
print("IMPORTANDO DATOS A CLEVER CLOUD")
print("="*80)
print(f"\nArchivo: {SQL_FILE}")
print(f"Destino: Clever Cloud MySQL")
print(f"Database: {DB_CONFIG['database']}")

file_size = os.path.getsize(SQL_FILE) / 1024 / 1024
print(f"Tamaño: {file_size:.2f} MB")

input(f"\n⚠️  Presiona ENTER para continuar o Ctrl+C para cancelar...")

try:
    print(f"\n🔌 Conectando a Clever Cloud...")
    connection = pymysql.connect(**DB_CONFIG)
    print("✅ Conectado")
    
    print(f"\n📥 Leyendo archivo SQL...")
    with open(SQL_FILE, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    # Dividir en statements individuales
    statements = []
    current_statement = []
    in_transaction = False
    
    for line in sql_content.split('\n'):
        line = line.strip()
        
        # Ignorar comentarios y líneas vacías
        if not line or line.startswith('--'):
            continue
        
        current_statement.append(line)
        
        # Si la línea termina con ; es el fin del statement
        if line.endswith(';'):
            stmt = ' '.join(current_statement)
            statements.append(stmt)
            current_statement = []
    
    print(f"   📊 {len(statements)} statements SQL encontrados")
    
    print(f"\n⏳ Ejecutando importación...")
    with connection.cursor() as cursor:
        success = 0
        errors = 0
        
        for idx, statement in enumerate(statements, 1):
            try:
                # Mostrar progreso cada 1000 statements
                if idx % 1000 == 0:
                    print(f"   Procesando: {idx}/{len(statements)} ({idx*100//len(statements)}%)")
                
                cursor.execute(statement)
                success += 1
                
            except Exception as e:
                errors += 1
                if errors <= 5:  # Mostrar solo primeros 5 errores
                    print(f"   ⚠️  Error en statement {idx}: {str(e)[:100]}")
        
        connection.commit()
    
    print(f"\n{'='*80}")
    print(f"✅ IMPORTACIÓN COMPLETADA")
    print(f"{'='*80}")
    print(f"Statements exitosos: {success:,}")
    if errors > 0:
        print(f"Errores: {errors}")
    
    # Verificación
    print(f"\n📊 Verificando datos importados...")
    with connection.cursor() as cursor:
        cursor.execute("SELECT COUNT(*) as total FROM registro_diario")
        print(f"   Registros diarios: {cursor.fetchone()['total']:,}")
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_hojas_ruta")
        print(f"   Hojas de ruta: {cursor.fetchone()['total']:,}")
        
        cursor.execute("SELECT COUNT(*) as total FROM contenedores_fisicos")
        print(f"   Contenedores: {cursor.fetchone()['total']:,}")
    
except KeyboardInterrupt:
    print(f"\n\n❌ Importación cancelada por el usuario")
except Exception as e:
    print(f"\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
finally:
    if 'connection' in locals():
        connection.close()

print("\n" + "="*80)
