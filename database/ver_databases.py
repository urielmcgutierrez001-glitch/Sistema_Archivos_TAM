#!/usr/bin/env python3
"""
Verificar base de datos local disponible
"""
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'charset': 'utf8mb4'
}

try:
    connection = pymysql.connect(**DB_CONFIG)
    
    with connection.cursor() as cursor:
        cursor.execute("SHOW DATABASES")
        databases = [row[0] for row in cursor.fetchall()]
        
        print("Bases de datos disponibles:")
        for db in databases:
            if 'tamep' in db.lower() or 'bf7' in db.lower():
                print(f"  ✓ {db}")
    
    connection.close()
    
except Exception as e:
    print(f"Error: {e}")
