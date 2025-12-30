#!/usr/bin/env python3
"""
Actualizar ubicaciones de contenedores desde Excel
Mapea ubicaciones del Excel a la tabla ubicaciones
"""

import pandas as pd
import pymysql
import os

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

# Mapeo de ubicaciones Excel → BD
MAPEO_UBICACIONES = {
    'Encomiendas': 'Encomiendas',
    'Encomiendas 1': 'Encomiendas',
    'Encomiendas 2': 'Encomiendas',
    'El Alto': 'El Alto',
    'Revisión': 'Revision',
    'Revision': 'Revision',
    'SECC. REVISION': 'Revision',
    'Secc. Revision': 'Revision',
    'Contrataciones': 'Contrataciones',
    'Almacenes': 'Almacenes',
    'SECC. JEFE DE CONTABILIDAD': 'SECC. JEFE DE CONTABILIDAD',
    'SECC. CONTABILIDAD': 'SECC. CONTABILIDAD',
    'SAL CONTA': 'SAL CONTA',
    'SALA CONTA': 'SALA CONTA',
    'Informatica': 'Informatica',
    'Informatica 2': 'Informatica 2',
}

EXCEL_FILES = [
    '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx',
    '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx',
]

def obtener_columna(df, nombres_posibles):
    """Busca columna por nombre"""
    for col in df.columns:
        col_norm = col.strip().replace('\n', ' ')
        if col_norm in nombres_posibles or col in nombres_posibles:
            return col
    return None

def normalizar_ubicacion(ubicacion_excel):
    """Mapea ubicación del Excel a nombre en BD"""
    if pd.isna(ubicacion_excel):
        return None
    
    ubicacion_str = str(ubicacion_excel).strip()
    return MAPEO_UBICACIONES.get(ubicacion_str, ubicacion_str)

print("="*80)
print("ACTUALIZANDO UBICACIONES DESDE EXCEL")
print("="*80)

connection = pymysql.connect(**DB_CONFIG)
actualizaciones = {}

try:
    with connection.cursor() as cursor:
        # Cargar ubicaciones de BD
        cursor.execute("SELECT id, nombre FROM ubicaciones")
        ubicaciones_bd = {row['nombre']: row['id'] for row in cursor.fetchall()}
        print(f"\n📍 Ubicaciones en BD: {len(ubicaciones_bd)}")
        
        for archivo in EXCEL_FILES:
            ruta = os.path.join(EXCEL_DIR, archivo)
            if not os.path.exists(ruta):
                continue
            
            print(f"\n📄 Procesando: {archivo}")
            df = pd.read_excel(ruta)
            
            # Buscar columnas
            col_contenedor = obtener_columna(df, [
                'NRO. LIBRO/AMARR', 'NRO. LIBRO AMARR',
                'NRO. LIBRO\nAMARR', 'NRO. LIBRO\nAMARRO',
                'NRO. LIBRO/     \n AMARR'
            ])
            
            col_ubicacion = obtener_columna(df, [
                'Ubicación Unidad/Área'
            ])
            
            col_color = obtener_columna(df, [
                'LIBRO COLOR', 'LIBRO\nCOLOR'
            ])
            
            if not col_contenedor or not col_ubicacion:
                print(f"   ⚠️  Columnas no encontradas")
                continue
            
            procesados = 0
            for idx, fila in df.iterrows():
                if pd.isna(fila[col_contenedor]) or pd.isna(fila[col_ubicacion]):
                    continue
                
                # Determinar tipo
                color = fila[col_color] if col_color else None
                tipo = 'LIBRO' if not pd.isna(color) else 'AMARRO'
                
                # Normalizar número
                numero = str(int(float(fila[col_contenedor])))
                
                # Normalizar ubicación
                ubicacion_excel = str(fila[col_ubicacion]).strip()
                ubicacion_bd = normalizar_ubicacion(ubicacion_excel)
                
                if not ubicacion_bd or ubicacion_bd not in ubicaciones_bd:
                    continue
                
                ubicacion_id = ubicaciones_bd[ubicacion_bd]
                
                # Guardar para actualizar
                clave = f"{tipo}-{numero}"
                actualizaciones[clave] = ubicacion_id
                procesados += 1
            
            print(f"   ✅ {procesados} contenedores con ubicación")
        
        # Actualizar en BD
        print(f"\n📝 Actualizando {len(actualizaciones)} contenedores...")
        actualizados = 0
        
        for clave, ubicacion_id in actualizaciones.items():
            tipo, numero = clave.split('-')
            
            sql = """
                UPDATE contenedores_fisicos
                SET ubicacion_id = %s
                WHERE tipo_contenedor = %s AND numero = %s
            """
            cursor.execute(sql, (ubicacion_id, tipo, numero))
            if cursor.rowcount > 0:
                actualizados += 1
        
        connection.commit()
        print(f"✅ {actualizados} contenedores actualizados con ubicación")
        
        # Verificar
        cursor.execute("""
            SELECT 
                COUNT(*) as total,
                COUNT(ubicacion_id) as con_ubicacion,
                COUNT(*) - COUNT(ubicacion_id) as sin_ubicacion
            FROM contenedores_fisicos
        """)
        stats = cursor.fetchone()
        print(f"\n📊 Estadísticas:")
        print(f"   Total: {stats['total']}")
        print(f"   Con ubicación: {stats['con_ubicacion']}")
        print(f"   Sin ubicación: {stats['sin_ubicacion']}")

except Exception as e:
    connection.rollback()
    print(f"❌ ERROR: {e}")
finally:
    connection.close()

print("\n✅ Proceso completado")
