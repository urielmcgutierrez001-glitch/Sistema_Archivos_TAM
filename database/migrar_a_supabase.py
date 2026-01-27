#!/usr/bin/env python3
"""
Script de Migración MySQL Local -> Supabase (PostgreSQL)
--------------------------------------------------------
Este script lee toda la base de datos local y la inserta en Supabase.
Se encarga de:
1. Leer tablas de MySQL
2. Crear tablas equivalentes en Postgres (autodetectando tipos)
3. Transferir datos en lotes
4. Manejar claves foráneas y secuencias
"""

import os
import pymysql
import psycopg2
import psycopg2.errors
from psycopg2.extras import execute_batch
import pandas as pd
import numpy as np
from datetime import datetime
import sys

# --- CONFIGURACIÓN ---
MYSQL_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

# Supabase Connection String
# Pooler Connection (IPv4 compatible)
SUPABASE_URI = "postgresql://postgres.sdovwowdbuzjfwtgnfoa:pasantiatam123@aws-1-us-east-1.pooler.supabase.com:6543/postgres"

# Mapeo de tipos MySQL -> PostgreSQL
TYPE_MAPPING = {
    'int': 'INTEGER',
    'tinyint': 'SMALLINT',
    'smallint': 'SMALLINT',
    'mediumint': 'INTEGER',
    'bigint': 'BIGINT',
    'varchar': 'VARCHAR',
    'char': 'CHAR',
    'text': 'TEXT',
    'mediumtext': 'TEXT',
    'longtext': 'TEXT',
    'datetime': 'TIMESTAMP',
    'timestamp': 'TIMESTAMP',
    'date': 'DATE',
    'float': 'FLOAT',
    'double': 'DOUBLE PRECISION',
    'decimal': 'DECIMAL',
    'boolean': 'BOOLEAN',
    'json': 'JSONB',
    'enum': 'VARCHAR' # Postgres tiene ENUM, pero VARCHAR es más seguro para migración rápida
}

def get_mysql_connection():
    return pymysql.connect(**MYSQL_CONFIG)

def get_pg_connection():
    return psycopg2.connect(SUPABASE_URI)

def map_mysql_type_to_pg(mysql_type, column_type_full):
    """Convierte tipos de MySQL a PostgreSQL"""
    base_type = mysql_type.lower()
    
    if 'unsigned' in column_type_full.lower():
        # Postgres no tiene unsigned nativo estándar, usamos el siguiente tamaño o lo dejamos igual
        pass

    if base_type in TYPE_MAPPING:
        return TYPE_MAPPING[base_type]
    
    # Casos especiales
    if 'int' in base_type: return 'INTEGER'
    if 'char' in base_type: return 'VARCHAR'
    if 'text' in base_type: return 'TEXT'
    
    return 'VARCHAR' # Default fallback

def migrate_table(table_name, mysql_conn, pg_conn):
    print(f"\n📦 Migrando tabla: {table_name}...")
    
    try:
        # 1. Leer estructura de MySQL
        with mysql_conn.cursor() as cursor:
            cursor.execute(f"DESCRIBE `{table_name}`")
            columns = cursor.fetchall()
            
            # Obtener PK
            cursor.execute(f"SHOW KEYS FROM `{table_name}` WHERE Key_name = 'PRIMARY'")
            pk_info = cursor.fetchone()
            pk_col = pk_info['Column_name'] if pk_info else None

        # 2. Construir CREATE TABLE para Postgres
        pg_cols = []
        for col in columns:
            name = col['Field']
            mysql_type = col['Type'].split('(')[0]
            pg_type = map_mysql_type_to_pg(mysql_type, col['Type'])
            
            # Ajustes de longitud para VARCHAR
            if 'varchar' in col['Type']:
                match = pd.io.common.re.search(r'\((\d+)\)', col['Type'])
                if match:
                    pg_type = f"VARCHAR({match.group(1)})"
            
            # Auto Increment -> SERIAL
            if col['Extra'] == 'auto_increment':
                pg_type = 'SERIAL'
            
            nullable = 'NULL' if col['Null'] == 'YES' else 'NOT NULL'
            default = ''
            
            # Manejar defaults
            if col['Default'] is not None:
                def_val = str(col['Default']).lower()
                if 'current_timestamp' in def_val:
                    default = 'DEFAULT CURRENT_TIMESTAMP'
                else:
                    # Basic quote handling
                    if isinstance(col['Default'], str):
                         default = f"DEFAULT '{col['Default']}'"
                    else:
                         default = f"DEFAULT {col['Default']}"
            elif col['Null'] == 'YES' and col['Default'] is None:
                default = 'DEFAULT NULL'

            # Evitar SERIAL NOT NULL DEFAULT ... (SERIAL implica not null y default sequence)
            if pg_type == 'SERIAL':
                nullable = ''
                default = ''

            col_def = f'"{name}" {pg_type} {nullable} {default}'.strip()
            pg_cols.append(col_def)

        if pk_col:
            pg_cols.append(f'PRIMARY KEY ("{pk_col}")')

        create_sql = f'CREATE TABLE IF NOT EXISTS "{table_name}" (\n    ' + ',\n    '.join(pg_cols) + '\n);'
        
        # 3. Crear tabla en Postgres
        with pg_conn.cursor() as pg_cursor:
            # Primero dropear si existe para asegurar limpieza (opcional, pero recomendado en migración full)
            pg_cursor.execute(f'DROP TABLE IF EXISTS "{table_name}" CASCADE;')
            pg_cursor.execute(create_sql)
            
        # 4. Leer datos de MySQL (Raw Cursor para mayor control)
        print("   📥 Leyendo datos de MySQL...")
        
        # Lista de columnas basada en el DESCRIBE anterior para asegurar orden
        col_names = [col['Field'] for col in columns]
        
        with mysql_conn.cursor() as cursor:
            cursor.execute(f"SELECT * FROM `{table_name}`")
            rows = cursor.fetchall()
            
        if not rows:
            print("   ⚠️  Tabla vacía, saltando inserción.")
            return

        # Convertir diccionarios a tuplas ordenadas
        data = []
        for row in rows:
            tup = []
            for col in col_names:
                val = row[col]
                # Limpieza básica de tipos
                if isinstance(val, (pd.Timestamp, datetime)):
                     val = str(val) # Convertir a string para evitar problemas de timezone/formato
                tup.append(val)
            data.append(tuple(tup))

        # 5. Insertar en Postgres
        print(f"   📤 Insertando {len(data)} registros en Postgres...")
        
        columns_list = ', '.join([f'"{c}"' for c in col_names])
        placeholders = ', '.join(['%s'] * len(col_names))
        insert_sql = f'INSERT INTO "{table_name}" ({columns_list}) VALUES ({placeholders})'
        
        with pg_conn.cursor() as pg_cursor:
            execute_batch(pg_cursor, insert_sql, data, page_size=1000)
            
            # 6. Resetear secuencias si hay SERIAL
            if pk_col:
                # Asumimos que la secuencia se llama table_id_seq por defecto en postgres serial
                seq_name = f"{table_name}_{pk_col}_seq"
                try:
                    # Verificar max id
                    pg_cursor.execute(f'SELECT MAX("{pk_col}") FROM "{table_name}"')
                    max_id = pg_cursor.fetchone()[0]
                    if max_id:
                        pg_cursor.execute(f"SELECT setval('{seq_name}', %s)", (max_id,))
                        print(f"   🔄 Secuencia {seq_name} actualizada a {max_id}")
                except psycopg2.Error:
                    # Puede que no sea identity/serial o tenga otro nombre, ignoramos error de secuencia no crítica
                    pg_conn.rollback()
                    pass

        pg_conn.commit()
        print("   ✅ Migración exitosa.")

    except Exception as e:
        pg_conn.rollback()
        print(f"   ❌ ERROR al migrar tabla {table_name}: {e}")
        # import traceback
        # traceback.print_exc()

def main():
    print("="*60)
    print("MIGRACIÓN A SUPABASE INICIADA")
    print("="*60)
    
    try:
        mysql_conn = get_mysql_connection()
        pg_conn = get_pg_connection()
        print("✅ Conexiones establecidas.")
        
        # Obtener lista de tablas
        with mysql_conn.cursor() as cursor:
            cursor.execute("SHOW TABLES")
            tables = [list(x.values())[0] for x in cursor.fetchall()]
        
        # Ordenar tablas para respetar FKs (simple approach: catalogs first)
        # Tablas independientes primero
        priority_tables = [
            'users', 
            'ubicaciones', 
            'tipos_contenedor', 
            'tipos_documento'
        ]
        
        # Tablas dependientes después
        dependent_tables = [
            'contenedores_fisicos',
            'documentos', 
            'prestamos',
            'prestamo_detalles',
            'clasificacion_contenedor_documento'
        ]
        
        ordered_tables = []
        # Agregar prioritarias si existen
        for t in priority_tables:
            if t in tables:
                ordered_tables.append(t)
        
        # Agregar el resto que no esté en prioritaria ni dependiente
        for t in tables:
            if t not in ordered_tables and t not in dependent_tables:
                ordered_tables.append(t)
        
        # Agregar dependientes al final
        for t in dependent_tables:
            if t in tables and t not in ordered_tables:
                ordered_tables.append(t)
        
        print(f"📋 Tablas encontradas: {len(tables)}")
        
        for table in ordered_tables:
            migrate_table(table, mysql_conn, pg_conn)
            
    except Exception as e:
        print(f"\n❌ ERROR GLOBAL: {e}")
    finally:
        if 'mysql_conn' in locals() and mysql_conn.open: mysql_conn.close()
        if 'pg_conn' in locals() and pg_conn.closed == 0: pg_conn.close()
        print("\n🏁 Proceso finalizado.")

if __name__ == "__main__":
    main()
