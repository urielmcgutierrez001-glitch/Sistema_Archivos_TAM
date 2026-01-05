"""
Script para arreglar la columna password en la tabla usuarios de Clever Cloud
"""
import pymysql

# Credenciales de Clever Cloud MySQL
DB_CONFIG = {
    'host': 'bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com',
    'user': 'uh5uxh0yxbs9cxva',
    'password': 'HdTIK6C8X5M5qsQUTXoE',
    'database': 'bf7yz05jw1xmnb2vukrs',
    'port': 3306,
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def main():
    print("Conectando a Clever Cloud MySQL...")
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    
    try:
        # 1. Ver estructura actual de usuarios
        print("\n1. Estructura actual de tabla usuarios:")
        cursor.execute("DESCRIBE usuarios")
        columns = cursor.fetchall()
        column_names = [col['Field'] for col in columns]
        for col in columns:
            print(f"   - {col['Field']}: {col['Type']}")
        
        # 2. Verificar si existe columna password
        if 'password' not in column_names:
            print("\n2. Columna 'password' NO existe. Agregando...")
            cursor.execute("ALTER TABLE usuarios ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER username")
            conn.commit()
            print("   ✓ Columna 'password' agregada")
        else:
            print("\n2. Columna 'password' ya existe")
        
        # 3. Ver usuarios actuales
        print("\n3. Usuarios actuales:")
        cursor.execute("SELECT id, username, password, nombre_completo, rol FROM usuarios")
        usuarios = cursor.fetchall()
        for u in usuarios:
            has_pass = "✓" if u['password'] else "✗"
            print(f"   - [{has_pass}] {u['username']} ({u['rol']})")
        
        # 4. Hash para admin123: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
        admin_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
        
        # 5. Actualizar usuarios sin password
        print("\n4. Actualizando usuarios sin password...")
        cursor.execute("""
            UPDATE usuarios 
            SET password = %s 
            WHERE password IS NULL OR password = ''
        """, (admin_hash,))
        updated = cursor.rowcount
        conn.commit()
        print(f"   ✓ {updated} usuarios actualizados con password 'admin123'")
        
        # 6. Verificar resultado
        print("\n5. Estado final:")
        cursor.execute("SELECT id, username, password IS NOT NULL as tiene_password, nombre_completo, rol FROM usuarios")
        usuarios = cursor.fetchall()
        for u in usuarios:
            has_pass = "✓" if u['tiene_password'] else "✗"
            print(f"   - [{has_pass}] {u['username']} ({u['rol']})")
        
        print("\n✅ COMPLETADO. Ahora puedes crear usuarios nuevos.")
        print("   Password por defecto de usuarios existentes: admin123")
        
    except Exception as e:
        print(f"ERROR: {e}")
        conn.rollback()
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    main()
