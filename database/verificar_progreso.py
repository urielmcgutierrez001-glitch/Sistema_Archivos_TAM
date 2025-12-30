#!/usr/bin/env python3
"""
Verificación rápida del progreso de importación
"""
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

try:
    connection = pymysql.connect(**DB_CONFIG)
    
    with connection.cursor() as cursor:
        print("="*60)
        print("PROGRESO DE IMPORTACIÓN")
        print("="*60)
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_diario")
        total_diario = cursor.fetchone()['total']
        print(f"\nRegistros en registro_diario: {total_diario:,}")
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_hojas_ruta")
        total_hr = cursor.fetchone()['total']
        print(f"Registros en registro_hojas_ruta: {total_hr:,}")
        
        cursor.execute("SELECT COUNT(*) as total FROM contenedores_fisicos")
        total_cont = cursor.fetchone()['total']
        print(f"Contenedores físicos: {total_cont:,}")
        
        cursor.execute("""
            SELECT tipo_documento, COUNT(*) as cantidad
            FROM registro_diario
            GROUP BY tipo_documento
            ORDER BY cantidad DESC
        """)
        
        print("\nRegistros por tipo de documento:")
        for row in cursor.fetchall():
            print(f"  {row['tipo_documento']:25} {row['cantidad']:>8,}")
        
        print("\n" + "="*60)
        
except Exception as e:
    print(f"Error: {e}")
finally:
    if 'connection' in locals():
        connection.close()
