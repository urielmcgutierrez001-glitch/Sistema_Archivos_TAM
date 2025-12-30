#!/usr/bin/env python3
"""
Crear base de datos local para importación
"""
import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'charset': 'utf8mb4'
}

DB_NAME = 'bf7yz05jw1xmnb2vukrs'

try:
    # Conectar sin especificar base de datos
    connection = pymysql.connect(**DB_CONFIG)
    
    with connection.cursor() as cursor:
        # Crear base de datos si no existe
        cursor.execute(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
        print(f"✅ Base de datos '{DB_NAME}' creada o ya existe")
        
        # Verificar
        cursor.execute("SHOW DATABASES")
        databases = [row[0] for row in cursor.fetchall()]
        
        if DB_NAME in databases:
            print(f"✅ Confirmado: Base de datos '{DB_NAME}' existe")
        else:
            print(f"❌ Error: Base de datos '{DB_NAME}' no se encontró")
    
    connection.close()
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()
