#!/usr/bin/env python3
"""
Limpieza de base de datos antes de reimportación
Ejecuta en Clever Cloud MySQL
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

print("="*80)
print("LIMPIEZA DE BASE DE DATOS - CLEVER CLOUD")
print("="*80)

try:
    connection = pymysql.connect(**DB_CONFIG)
    print("✅ Conectado a Clever Cloud MySQL")
    
    with connection.cursor() as cursor:
        # Deshabilitar checks
        cursor.execute("SET SQL_SAFE_UPDATES = 0")
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
        
        # Limpiar tablas
        print("\n🗑️  Limpiando tablas...")
        
        cursor.execute("TRUNCATE TABLE prestamos")
        print("   ✓ Préstamos eliminados")
        
        cursor.execute("DELETE FROM clasificacion_contenedor_documento WHERE id > 0")
        print("   ✓ Clasificaciones eliminadas")
        
        cursor.execute("TRUNCATE TABLE registro_diario")
        print("   ✓ Registros diarios eliminados")
        
        cursor.execute("TRUNCATE TABLE registro_hojas_ruta")
        print("   ✓ Hojas de ruta eliminadas")
        
        cursor.execute("DELETE FROM contenedores_fisicos WHERE id > 0")
        print("   ✓ Contenedores físicos eliminados")
        
        # Resetear auto_increment
        print("\n🔄 Reseteando auto_increment...")
        cursor.execute("ALTER TABLE contenedores_fisicos AUTO_INCREMENT = 1")
        cursor.execute("ALTER TABLE registro_diario AUTO_INCREMENT = 1")
        cursor.execute("ALTER TABLE registro_hojas_ruta AUTO_INCREMENT = 1")
        cursor.execute("ALTER TABLE clasificacion_contenedor_documento AUTO_INCREMENT = 1")
        cursor.execute("ALTER TABLE prestamos AUTO_INCREMENT = 1")
        print("   ✓ Auto_increment reseteado")
        
        # Verificación
        print("\n📊 Verificación post-limpieza:")
        cursor.execute("SELECT COUNT(*) as total FROM contenedores_fisicos")
        print(f"   Contenedores: {cursor.fetchone()['total']}")
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_diario")
        print(f"   Registros diarios: {cursor.fetchone()['total']}")
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_hojas_ruta")
        print(f"   Hojas de ruta: {cursor.fetchone()['total']}")
        
        cursor.execute("SELECT COUNT(*) as total FROM clasificacion_contenedor_documento")
        print(f"   Clasificaciones: {cursor.fetchone()['total']}")
        
        cursor.execute("SELECT COUNT(*) as total FROM prestamos")
        print(f"   Préstamos: {cursor.fetchone()['total']}")
        
        # Restaurar checks
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
        cursor.execute("SET SQL_SAFE_UPDATES = 1")
        
        connection.commit()
        
    print("\n" + "="*80)
    print("✅ BASE DE DATOS LIMPIA Y LISTA PARA REIMPORTACIÓN")
    print("="*80)
    
except Exception as e:
    print(f"\n❌ ERROR: {e}")
    import traceback
    traceback.print_exc()
finally:
    if 'connection' in locals():
        connection.close()
