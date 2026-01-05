#!/usr/bin/env python3
"""
Diagnóstico de contenedores físicos
"""
import pymysql

DB_CONFIG = {
    'host': 'bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com',
    'user': 'uh5uxh0yxbs9cxva',
    'password': 'HdTIK6C8X5M5qsQUTXoE',
    'database': 'bf7yz05jw1xmnb2vukrs',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

print("="*60)
print("DIAGNÓSTICO DE CONTENEDORES FÍSICOS")
print("="*60)

try:
    conn = pymysql.connect(**DB_CONFIG)
    with conn.cursor() as cursor:
        # Ver contenedores actuales
        cursor.execute('SELECT * FROM contenedores_fisicos')
        contenedores = cursor.fetchall()
        print(f'\nCONTENEDORES EXISTENTES: {len(contenedores)}')
        print('-'*60)
        for c in contenedores:
            print(f"ID: {c['id']:3} | Tipo: {c['tipo_contenedor']:8} | Numero: {str(c['numero']):5} | Color: {c.get('color', 'N/A')}")
        
        # Contar registros sin contenedor asignado
        cursor.execute('SELECT COUNT(*) as total FROM registro_diario WHERE contenedor_fisico_id IS NULL')
        sin_contenedor = cursor.fetchone()['total']
        
        cursor.execute('SELECT COUNT(*) as total FROM registro_diario WHERE contenedor_fisico_id IS NOT NULL')
        con_contenedor = cursor.fetchone()['total']
        
        print(f'\nREGISTROS EN registro_diario:')
        print(f'  Con contenedor: {con_contenedor:,}')
        print(f'  Sin contenedor: {sin_contenedor:,}')
        
    conn.close()
    print("\n" + "="*60)
except Exception as e:
    print(f"Error: {e}")
