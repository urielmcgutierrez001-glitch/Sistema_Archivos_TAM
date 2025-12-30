#!/usr/bin/env python3
"""
RE-IMPORTACIÓN CORREGIDA DE CONTENEDORES Y DATOS
Versión 2.0 - Detecta columnas con saltos de línea correctamente
"""

import pandas as pd
import pymysql
import re
import os
from datetime import datetime

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'tamep_archivos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

EXCEL_FILES = [
    ('REGISTRO_DIARIO', '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_INGRESO', '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_CEPS', '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('PREVENTIVOS', '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('ASIENTOS_MANUALES', '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('DIARIOS_APERTURA', '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_TRASPASO', '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
]

def encontrar_columna(df, patrones):
    """Busca columna que coincida con algún patrón (ignora espacios y saltos de línea)"""
    for col in df.columns:
        col_limpio = col.strip().replace('\n', ' ').replace('  ', ' ')
        for patron in patrones:
            if patron.lower() in col_limpio.lower():
                return col
    return None

def normalizar_numero_contenedor(valor):
    """
    L-1 → 1
    L -1 → 1
    1067 → 1067
    """
    if pd.isna(valor):
        return None
    
    valor_str = str(valor).strip()
    
    # Patrón L-N
    match = re.match(r'^L\s*-?\s*(\d+)$', valor_str, re.IGNORECASE)
    if match:
        return match.group(1)
    
    # Número directo
    try:
        return str(int(float(valor_str)))
    except:
        return valor_str

def limpiar_valor(valor):
    if pd.isna(valor):
        return None
    if isinstance(valor, str) and valor.strip().lower() in ['nan', 's/n', '', 'n/a']:
        return None
    return str(valor).strip() if not isinstance(valor, (int, float)) else valor

print("="*80)
print("RE-IMPORTACIÓN CORREGIDA DE CONTENEDORES")
print("="*80)

connection = pymysql.connect(**DB_CONFIG)

# Cache de contenedores ya creados
contenedores_cache = {}
# Cache de ubicaciones
ubicaciones_cache = {}

try:
    with connection.cursor() as cursor:
        # Cargar ubicaciones
        cursor.execute("SELECT id, nombre FROM ubicaciones")
        for row in cursor.fetchall():
            ubicaciones_cache[row['nombre']] = row['id']
        
        print(f"\n📍 {len(ubicaciones_cache)} ubicaciones en BD")
        
        # Procesar cada Excel
        for tipo_doc, archivo in EXCEL_FILES:
            ruta = os.path.join(EXCEL_DIR, archivo)
            if not os.path.exists(ruta):
                continue
            
            print(f"\n{'='*80}")
            print(f"📄 {tipo_doc}: {archivo}")
            print(f"{'='*80}")
            
            df = pd.read_excel(ruta)
            print(f"   📊 {len(df)} filas en Excel")
            
            # Buscar columnas (patrones flexibles)
            col_contenedor = encontrar_columna(df, ['LIBRO', 'AMARR'])
            col_bloque = encontrar_columna(df, ['BLOQUE', 'NIVEL'])
            col_color = encontrar_columna(df, ['COLOR'])
            col_ubicacion = encontrar_columna(df, ['Ubicación', 'Unidad/Área'])
            col_abc = encontrar_columna(df, ['ABC'])
            col_gestion = encontrar_columna(df, ['GESTION', 'GESTIÓN'])
            col_comprobante = encontrar_columna(df, ['COMPROBANTE', 'DIARIO', 'INGRESO', 'CEPS', 'PREVENTIVO', 'MANUAL', 'TRASPASO'])
            
            print(f"   📋 Columnas detectadas:")
            print(f"      Contenedor: {col_contenedor}")
            print(f"      Bloque: {col_bloque}")
            print(f"      Color: {col_color}")
            print(f"      Ubicación: {col_ubicacion}")
            
            if not col_contenedor:
                print(f"   ⚠️  No se encontró columna de contenedor, saltando...")
                continue
            
            contenedores_creados = 0
            docs_actualizados = 0
            errores = 0
            
            for idx, fila in df.iterrows():
                try:
                    # Obtener datos
                    numero_raw = fila[col_contenedor]
                    if pd.isna(numero_raw):
                        continue
                    
                    numero = normalizar_numero_contenedor(numero_raw)
                    if not numero:
                        continue
                    
                    bloque = limpiar_valor(fila[col_bloque]) if col_bloque else None
                    color = limpiar_valor(fila[col_color]) if col_color else None
                    ubicacion_nombre = limpiar_valor(fila[col_ubicacion]) if col_ubicacion else None
                    abc = limpiar_valor(fila[col_abc]) if col_abc else None
                    gestion = int(fila[col_gestion]) if col_gestion and not pd.isna(fila[col_gestion]) else None
                    comprobante = limpiar_valor(fila[col_comprobante]) if col_comprobante else None
                    
                    # Determinar tipo
                    tipo = 'LIBRO' if color else 'AMARRO'
                    
                    # Buscar ubicacion_id
                    ubicacion_id = None
                    if ubicacion_nombre:
                        # Mapeos comunes
                        mapeo_ubicaciones = {
                            'Encomiendas': 'Encomiendas',
                            'Encomiendas 1': 'Encomiendas',
                            'Encomiendas 2': 'Encomiendas',
                            'Revisión': 'Revision',
                            'Revision': 'Revision',
                            'El Alto': 'El Alto',
                            'Contrataciones': 'Contrataciones',
                            'Almacenes': 'Almacenes',
                        }
                        ubicacion_bd = mapeo_ubicaciones.get(ubicacion_nombre, ubicacion_nombre)
                        ubicacion_id = ubicaciones_cache.get(ubicacion_bd)
                    
                    # Crear o buscar contenedor
                    clave_contenedor = f"{tipo}-{numero}"
                    
                    if clave_contenedor not in contenedores_cache:
                        # Buscar en BD
                        cursor.execute(
                            "SELECT id FROM contenedores_fisicos WHERE tipo_contenedor = %s AND numero = %s",
                            (tipo, numero)
                        )
                        resultado = cursor.fetchone()
                        
                        if resultado:
                            contenedor_id = resultado['id']
                        else:
                            # Crear nuevo
                            cursor.execute("""
                                INSERT INTO contenedores_fisicos 
                                (tipo_contenedor, numero, bloque_nivel, color, ubicacion_id, activo)
                                VALUES (%s, %s, %s, %s, %s, 1)
                            """, (tipo, numero, bloque, color, ubicacion_id))
                            contenedor_id = cursor.lastrowid
                            contenedores_creados += 1
                        
                        contenedores_cache[clave_contenedor] = contenedor_id
                    else:
                        contenedor_id = contenedores_cache[clave_contenedor]
                    
                    # Actualizar documento con contenedor
                    if gestion and comprobante and contenedor_id:
                        cursor.execute("""
                            UPDATE registro_diario
                            SET contenedor_fisico_id = %s,
                                codigo_abc = %s
                            WHERE gestion = %s 
                            AND nro_comprobante = %s
                            AND tipo_documento_id = (
                                SELECT id FROM tipo_documento WHERE codigo = %s
                            )
                        """, (contenedor_id, abc, gestion, comprobante, tipo_doc))
                        
                        if cursor.rowcount > 0:
                            docs_actualizados += cursor.rowcount
                    
                except Exception as e:
                    errores += 1
                    if errores <= 3:
                        print(f"   ⚠️  Error fila {idx + 2}: {str(e)[:80]}")
                    continue
            
            connection.commit()
            print(f"\n   ✅ Contenedores creados: {contenedores_creados}")
            print(f"   ✅ Documentos actualizados: {docs_actualizados}")
            if errores > 0:
                print(f"   ⚠️  Errores: {errores}")
        
        # Estadísticas finales
        cursor.execute("SELECT COUNT(*) as total FROM contenedores_fisicos")
        total_contenedores = cursor.fetchone()['total']
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_diario WHERE contenedor_fisico_id IS NOT NULL")
        total_asignados = cursor.fetchone()['total']
        
        cursor.execute("SELECT COUNT(*) as total FROM registro_diario")
        total_docs = cursor.fetchone()['total']
        
        print(f"\n{'='*80}")
        print(f"✅ IMPORTACIÓN COMPLETADA")
        print(f"{'='*80}")
        print(f"Contenedores creados: {total_contenedores}")
        print(f"Documentos con contenedor: {total_asignados} de {total_docs}")
        print(f"Porcentaje: {total_asignados*100.0/total_docs:.1f}%")
        
except Exception as e:
    connection.rollback()
    print(f"\n❌ ERROR: {e}")
    import traceback
    traceback.print_exc()
finally:
    connection.close()

print("\n📊 Ejecuta verificación para confirmar resultados")
