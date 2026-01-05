#!/usr/bin/env python3
"""
RE-IMPORTACIÓN CORREGIDA DE CONTENEDORES Y DATOS
Versión 5.0 - Usa columna tipo_documento para mapear documentos
  - registro_diario contiene todos los 7 tipos de documentos
  - tipo_documento es la columna que diferencia el tipo (ENUM o texto)
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

# Tuplas: (tipo_documento_valor en BD, nombre archivo Excel, patrones columna comprobante)
# El valor debe coincidir con el valor exacto de tipo_documento en registro_diario
EXCEL_FILES = [
    ('REGISTRO_DIARIO', '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['DIARIO', 'COMPROBANTE']),
    ('REGISTRO_INGRESO', '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['INGRESO', 'COMPROBANTE']),
    ('REGISTRO_CEPS', '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['CEPS', 'COMPROBANTE', 'EGRESO']),
    ('PREVENTIVOS', '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx', ['PREVENTIVO', 'COMPROBANTE']),
    ('ASIENTOS_MANUALES', '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx', ['MANUAL', 'ASIENTO', 'COMPROBANTE']),
    ('DIARIOS_APERTURA', '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx', ['APERTURA', 'DIARIO', 'COMPROBANTE']),
    ('REGISTRO_TRASPASO', '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx', ['TRASPASO', 'COMPROBANTE']),
]

def encontrar_columna(df, patrones, excluir_primera=False):
    """Busca columna que coincida con algún patrón (ignora espacios y saltos de línea)"""
    columnas = list(df.columns)
    inicio = 1 if excluir_primera and len(columnas) > 1 else 0
    
    for col in columnas[inicio:]:
        col_limpio = col.strip().replace('\n', ' ').replace('  ', ' ')
        for patron in patrones:
            if patron.lower() in col_limpio.lower():
                return col
    return None

def normalizar_numero_contenedor(valor):
    if pd.isna(valor):
        return None
    
    valor_str = str(valor).strip()
    
    match = re.match(r'^L\s*-?\s*(\d+)$', valor_str, re.IGNORECASE)
    if match:
        return match.group(1)
    
    try:
        return str(int(float(valor_str)))
    except:
        return valor_str

def normalizar_nro_comprobante(valor):
    """
    Normaliza número de comprobante:
    A-00001 → 1
    A-00020 → 20
    00005 → 5
    5 → 5
    """
    if pd.isna(valor):
        return None
    
    valor_str = str(valor).strip()
    
    # Patrón A-NNNNN o similar con letras
    match = re.match(r'^[A-Za-z]-?0*(\d+)$', valor_str)
    if match:
        return match.group(1)
    
    # Patrón solo números con ceros a la izquierda
    match = re.match(r'^0*(\d+)$', valor_str)
    if match:
        return match.group(1)
    
    # Intentar convertir directamente
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
print("RE-IMPORTACIÓN CORREGIDA DE CONTENEDORES V5")
print("Usa tipo_documento para mapear documentos")
print("="*80)

connection = pymysql.connect(**DB_CONFIG)

contenedores_cache = {}
ubicaciones_cache = {}

try:
    with connection.cursor() as cursor:
        # Primero ver qué valores de tipo_documento existen
        print("\n📋 Verificando valores de tipo_documento en BD...")
        cursor.execute("""
            SELECT tipo_documento, COUNT(*) as cantidad 
            FROM registro_diario 
            WHERE tipo_documento IS NOT NULL
            GROUP BY tipo_documento
            ORDER BY cantidad DESC
        """)
        tipo_doc_existentes = {}
        print("   Valores encontrados:")
        for row in cursor.fetchall():
            tipo_doc_existentes[row['tipo_documento']] = int(row['cantidad'])
            print(f"      {row['tipo_documento']}: {row['cantidad']} registros")
        
        # Verificar estructura de contenedores_fisicos
        print("\n🔧 Verificando estructura de tabla contenedores_fisicos...")
        cursor.execute("""
            SELECT COUNT(*) as tiene 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = 'tamep_archivos' 
            AND TABLE_NAME = 'contenedores_fisicos' 
            AND COLUMN_NAME = 'tipo_documento'
        """)
        if cursor.fetchone()['tiene'] == 0:
            print("   Agregando columna tipo_documento...")
            cursor.execute("""
                ALTER TABLE contenedores_fisicos 
                ADD COLUMN tipo_documento VARCHAR(50) NULL AFTER tipo_contenedor
            """)
            connection.commit()
            print("   ✅ Columna agregada")
        else:
            print("   ✅ Columna ya existe")
        
        # Limpiar
        print("\n🗑️  Limpiando contenedores anteriores...")
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
        cursor.execute("UPDATE registro_diario SET contenedor_fisico_id = NULL")
        cursor.execute("UPDATE registro_hojas_ruta SET contenedor_fisico_id = NULL")
        cursor.execute("UPDATE registro_egreso SET contenedor_fisico_id = NULL")
        cursor.execute("DELETE FROM contenedores_fisicos")
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
        connection.commit()
        print("   ✅ Limpieza completada")
        
        # Cargar ubicaciones
        cursor.execute("SELECT id, nombre FROM ubicaciones")
        for row in cursor.fetchall():
            ubicaciones_cache[row['nombre']] = row['id']
        
        print(f"\n📍 {len(ubicaciones_cache)} ubicaciones en BD")
        
        # Procesar cada Excel
        for tipo_doc_excel, archivo, patrones_comprobante in EXCEL_FILES:
            ruta = os.path.join(EXCEL_DIR, archivo)
            if not os.path.exists(ruta):
                print(f"\n⚠️  Archivo no encontrado: {archivo}")
                continue
            
            # Verificar si este tipo existe en BD
            tipo_doc_bd = tipo_doc_excel
            if tipo_doc_excel not in tipo_doc_existentes:
                print(f"\n⚠️  tipo_documento '{tipo_doc_excel}' no existe en BD, buscando similar...")
                # Intentar encontrar match parcial
                for td in tipo_doc_existentes:
                    if tipo_doc_excel in td or td in tipo_doc_excel:
                        tipo_doc_bd = td
                        print(f"   Usando '{tipo_doc_bd}' en su lugar")
                        break
            
            print(f"\n{'='*80}")
            print(f"📄 {tipo_doc_excel} -> BD:{tipo_doc_bd}: {archivo}")
            print(f"{'='*80}")
            
            df = pd.read_excel(ruta)
            print(f"   📊 {len(df)} filas en Excel")
            
            col_contenedor = encontrar_columna(df, ['LIBRO', 'AMARR'], excluir_primera=True)
            col_bloque = encontrar_columna(df, ['BLOQUE', 'NIVEL'], excluir_primera=True)
            col_color = encontrar_columna(df, ['COLOR'], excluir_primera=True)
            col_ubicacion = encontrar_columna(df, ['Ubicación', 'Unidad/Área'], excluir_primera=True)
            col_abc = encontrar_columna(df, ['ABC'], excluir_primera=True)
            col_gestion = encontrar_columna(df, ['GESTION', 'GESTIÓN'], excluir_primera=True)
            col_comprobante = encontrar_columna(df, patrones_comprobante, excluir_primera=True)
            
            print(f"   📋 Columnas detectadas:")
            print(f"      Contenedor: {col_contenedor}")
            print(f"      Bloque: {col_bloque}")
            print(f"      Color: {col_color}")
            print(f"      Gestión: {col_gestion}")
            print(f"      Comprobante: {col_comprobante}")
            
            if not col_contenedor:
                print(f"   ⚠️  No se encontró columna de contenedor, saltando...")
                continue
            
            contenedores_creados = 0
            docs_actualizados = 0
            errores = 0
            
            for idx, fila in df.iterrows():
                try:
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
                    gestion = None
                    if col_gestion and not pd.isna(fila[col_gestion]):
                        try:
                            gestion = int(float(fila[col_gestion]))
                        except:
                            pass
                    
                    comprobante = None
                    if col_comprobante and not pd.isna(fila[col_comprobante]):
                        comprobante = normalizar_nro_comprobante(fila[col_comprobante])
                    
                    tipo = 'LIBRO' if color else 'AMARRO'
                    
                    ubicacion_id = None
                    if ubicacion_nombre:
                        mapeo_ubicaciones = {
                            'Encomiendas': 'Encomiendas',
                            'Encomiendas 1': 'Encomiendas',
                            'Encomiendas 2': 'Encomiendas',
                            'Revisión': 'Revision',
                            'Revision': 'Revision',
                            'El Alto': 'El Alto',
                            'Contrataciones': 'Contrataciones',
                            'Almacenes': 'Almacenes',
                            'Informatica 1': 'Informatica',
                            'Informatica 2': 'Informatica',
                            'Informatica': 'Informatica',
                        }
                        ubicacion_bd = mapeo_ubicaciones.get(ubicacion_nombre, ubicacion_nombre)
                        ubicacion_id = ubicaciones_cache.get(ubicacion_bd)
                    
                    # Clave única: tipo_contenedor + numero + tipo_documento
                    clave_contenedor = f"{tipo}-{numero}-{tipo_doc_excel}"
                    
                    if clave_contenedor not in contenedores_cache:
                        cursor.execute(
                            """SELECT id FROM contenedores_fisicos 
                               WHERE tipo_contenedor = %s AND numero = %s AND tipo_documento = %s""",
                            (tipo, numero, tipo_doc_excel)
                        )
                        resultado = cursor.fetchone()
                        
                        if resultado:
                            contenedor_id = resultado['id']
                        else:
                            cursor.execute("""
                                INSERT INTO contenedores_fisicos 
                                (tipo_contenedor, tipo_documento, numero, bloque_nivel, color, ubicacion_id, activo)
                                VALUES (%s, %s, %s, %s, %s, %s, 1)
                            """, (tipo, tipo_doc_excel, numero, bloque, color, ubicacion_id))
                            contenedor_id = cursor.lastrowid
                            contenedores_creados += 1
                        
                        contenedores_cache[clave_contenedor] = contenedor_id
                    else:
                        contenedor_id = contenedores_cache[clave_contenedor]
                    
                    # *** CAMBIO PRINCIPAL: Usar tipo_documento en vez de tabla_origen ***
                    if gestion and comprobante and contenedor_id:
                        cursor.execute("""
                            UPDATE registro_diario
                            SET contenedor_fisico_id = %s,
                                codigo_abc = %s
                            WHERE gestion = %s 
                            AND nro_comprobante = %s
                            AND tipo_documento = %s
                        """, (contenedor_id, abc, gestion, comprobante, tipo_doc_bd))
                        
                        if cursor.rowcount > 0:
                            docs_actualizados += cursor.rowcount
                    
                except Exception as e:
                    errores += 1
                    if errores <= 5:
                        print(f"   ⚠️  Error fila {idx + 2}: {str(e)[:100]}")
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
        if total_docs > 0:
            print(f"Porcentaje: {total_asignados*100.0/total_docs:.1f}%")
        
        # Desglose por tipo_documento
        print(f"\n📊 Desglose por tipo_documento:")
        cursor.execute("""
            SELECT tipo_documento, 
                   COUNT(*) as total,
                   SUM(CASE WHEN contenedor_fisico_id IS NOT NULL THEN 1 ELSE 0 END) as con_contenedor
            FROM registro_diario
            GROUP BY tipo_documento
            ORDER BY tipo_documento
        """)
        for row in cursor.fetchall():
            total = int(row['total'])
            con = int(row['con_contenedor'])
            pct = (con * 100.0 / total) if total > 0 else 0
            print(f"   {row['tipo_documento']}: {con}/{total} ({pct:.1f}%)")
        
        print(f"\n📦 Contenedores por tipo:")
        cursor.execute("""
            SELECT tipo_documento, tipo_contenedor, COUNT(*) as cantidad
            FROM contenedores_fisicos
            GROUP BY tipo_documento, tipo_contenedor
            ORDER BY tipo_documento, tipo_contenedor
        """)
        for row in cursor.fetchall():
            print(f"   {row['tipo_documento']} - {row['tipo_contenedor']}: {row['cantidad']}")
        
except Exception as e:
    connection.rollback()
    print(f"\n❌ ERROR: {e}")
    import traceback
    traceback.print_exc()
finally:
    connection.close()

print("\n📊 Proceso finalizado")
