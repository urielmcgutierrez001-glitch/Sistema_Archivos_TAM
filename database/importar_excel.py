#!/usr/bin/env python3
"""
Importador de Excel a MySQL - Sistema TAMEP (CORREGIDO)
===========================================
Procesa 8 archivos Excel con mapeo correcto de columnas
Maneja rangos de documentos (ej: "12-20" se expande a 12,13,14...20)
"""

import pandas as pd
import pymysql
import re
import os
from datetime import datetime

# =====================================================
# CONFIGURACIÓN DE BASE DE DATOS
# =====================================================
DB_CONFIG = {
    'host': 'bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com',
    'user': 'uh5uxh0yxbs9cxva',
    'password': 'HdTIK6C8X5M5qsQUTXoE',
    'database': 'bf7yz05jw1xmnb2vukrs',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

# =====================================================
# RUTAS DE ARCHIVOS EXCEL
# =====================================================
EXCEL_DIR = r"C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Datos Excel"

EXCEL_FILES = [
    ('REGISTRO_DIARIO', '01 REGISTRO DIARIO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_INGRESO', '02 REGISTRO INGRESO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_CEPS', '03 REGISTRO CEPS TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('PREVENTIVOS', '04 PREVENTIVOS TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('ASIENTOS_MANUALES', '05 ASIENTOS MANUALES TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('DIARIOS_APERTURA', '06 DIARIOS DE APERTURA TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('REGISTRO_TRASPASO', '07 REGISTRO TRASPASO TAMEP ARCHIVOS 2007 - 2026.xlsx'),
    ('HOJA_RUTA_DIARIOS', '08 HOJAS DE RUTA - DIARIOS TAMEP ARCHIVOS 2007 - 2026.xlsx')
]

# =====================================================
# MAPEO DE COLUMNAS EXCEL → BD
# =====================================================
MAPEO_COLUMNAS = {
    'GESTION': 'gestion',
    'GESTIÓN': 'gestion',
    'NRO. COMPROBANTE DIARIO': 'nro_comprobante',
    'NRO. DE COMPROBANTE DIARIO': 'nro_comprobante',
    'NRO. PREVENTIVOS': 'nro_comprobante',
    'PREVENTIVOS': 'nro_comprobante',
    'NRO. PREVENTIVOS': 'nro_comprobante',
    'ASIENTOS MANUALES': 'nro_comprobante',
    'COMPROBANTE DE CONTABILIDAD TRASPASO': 'nro_comprobante',
    'NRO. COMPROBANTE DE CONTABILIDAD TRASPASO': 'nro_comprobante',
    'NRO INGRESO': 'nro_comprobante',
    'NRO. INGRESO': 'nro_comprobante',
    'NRO. CEPS': 'nro_comprobante',
    'ABC/1,1:1,2:1,3…': 'codigo_abc',
    'NRO. LIBRO/AMARR': 'contenedor_numero',
    'NRO. LIBRO AMARR': 'contenedor_numero',
    'NRO. LIBRO\nAMARR': 'contenedor_numero',
    'NRO. LIBRO\nAMARRO': 'contenedor_numero',
    'BLOQUE/NIVEL': 'bloque_nivel',
    'BLOQUE / NIVEL': 'bloque_nivel',
    'BLOQUE\nNIVEL': 'bloque_nivel',
    'LIBRO COLOR': 'libro_color',
    'LIBRO\nCOLOR': 'libro_color',
    'Ubicación Unidad/Área': 'ubicacion',
    'OBSERVACIONES': 'observaciones',
    'OBS.': 'observaciones'
}

# =====================================================
# FUNCIONES AUXILIARES
# =====================================================

def normalizar_columna(nombre_col):
    """Normaliza nombre de columna para búsqueda"""
    return nombre_col.strip().replace('\n', ' ')

def obtener_columna_mapeada(df, posibles_nombres):
    """Busca una columna por varios nombres posibles"""
    for col in df.columns:
        col_norm = normalizar_columna(col)
        if col_norm in posibles_nombres or col in posibles_nombres:
            return col
    return None

def expandir_rango(valor):
    """Expande rangos como "12-20" en [12, 13, 14, ..., 20]"""
    if pd.isna(valor):
        return []
    
    valor_str = str(valor).strip()
    patron_rango = r'^(\d+)\s*-\s*(\d+)$'
    match = re.match(patron_rango, valor_str)
    
    if match:
        inicio = int(match.group(1))
        fin = int(match.group(2))
        return list(range(inicio, fin + 1))
    else:
        try:
            # Intentar convertir a número
            return [int(float(valor_str))]
        except:
            # Si no es número, retornar como string
            return [valor_str]

def limpiar_valor(valor):
    """Limpia valores NaN y los convierte a None para SQL"""
    if pd.isna(valor):
        return None
    if isinstance(valor, str) and valor.strip().lower() in ['nan', 's/n', '']:
        return None
    return valor

def obtener_o_crear_contenedor(cursor, tipo_contenedor, numero, bloque_nivel=None, color=None, ubicacion_nombre=None):
    """Obtiene un contenedor existente o lo crea"""
    if not numero or pd.isna(numero):
        return None
    
    numero = str(int(float(numero))) if isinstance(numero, (int, float)) else str(numero)
    
    # Buscar contenedor existente
    sql_buscar = """
        SELECT id FROM contenedores_fisicos 
        WHERE tipo_contenedor = %s AND numero = %s
    """
    cursor.execute(sql_buscar, (tipo_contenedor, numero))
    resultado = cursor.fetchone()
    
    if resultado:
        return resultado['id']
    
    # Buscar ubicación
    ubicacion_id = None
    if ubicacion_nombre:
        cursor.execute("SELECT id FROM ubicaciones WHERE nombre = %s", (ubicacion_nombre,))
        ub = cursor.fetchone()
        if ub:
            ubicacion_id = ub['id']
    
    # Crear nuevo contenedor
    sql_crear = """
        INSERT INTO contenedores_fisicos 
        (tipo_contenedor, numero, bloque_nivel, color, ubicacion_id, activo)
        VALUES (%s, %s, %s, %s, %s, 1)
    """
    cursor.execute(sql_crear, (tipo_contenedor, numero, bloque_nivel, color, ubicacion_id))
    contenedor_id = cursor.lastrowid
    
    return contenedor_id

def actualizar_clasificacion(cursor, contenedor_id, tipo_documento, gestion):
    """Actualiza la tabla de clasificación"""
    if not contenedor_id or not gestion:
        return
    
    try:
        sql_verificar = """
            SELECT id FROM clasificacion_contenedor_documento
            WHERE contenedor_id = %s 
            AND tipo_documento = %s
            AND (gestion_desde IS NULL OR gestion_desde <= %s)
            AND (gestion_hasta IS NULL OR gestion_hasta >= %s)
        """
        cursor.execute(sql_verificar, (contenedor_id, tipo_documento, gestion, gestion))
        resultado = cursor.fetchone()
        
        if resultado:
            sql_actualizar = """
                UPDATE clasificacion_contenedor_documento
                SET cantidad_documentos = cantidad_documentos + 1
                WHERE id = %s
            """
            cursor.execute(sql_actualizar, (resultado['id'],))
        else:
            sql_crear = """
                INSERT INTO clasificacion_contenedor_documento
                (contenedor_id, tipo_documento, gestion_desde, gestion_hasta, cantidad_documentos)
                VALUES (%s, %s, %s, %s, 1)
            """
            cursor.execute(sql_crear, (contenedor_id, tipo_documento, gestion, gestion))
    except Exception as e:
        print(f"    ⚠️  Error al actualizar clasificación: {e}")

# =====================================================
# FUNCIÓN PRINCIPAL DE IMPORTACIÓN
# =====================================================

def importar_excel(tipo_documento, archivo_excel):
    """Importa un archivo Excel a la base de datos"""
    print(f"\n{'='*80}")
    print(f"📄 Importando: {tipo_documento}")
    print(f"   Archivo: {archivo_excel}")
    print(f"{'='*80}")
    
    ruta_completa = os.path.join(EXCEL_DIR, archivo_excel)
    
    if not os.path.exists(ruta_completa):
        print(f"❌ ERROR: Archivo no encontrado")
        return 0
    
    # Leer Excel
    try:
        df = pd.read_excel(ruta_completa)
        print(f"📊 Filas en Excel: {len(df)}")
    except Exception as e:
        print(f"❌ ERROR al leer Excel: {e}")
        return 0
    
    # Conectar a base de datos
    connection = pymysql.connect(**DB_CONFIG)
    total_insertados = 0
    errores = 0
    
    try:
        with connection.cursor() as cursor:
            for idx, fila in df.iterrows():
                try:
                    # Obtener gestión
                    col_gestion = obtener_columna_mapeada(df, ['GESTION', 'GESTIÓN'])
                    gestion = limpiar_valor(fila[col_gestion]) if col_gestion else None
                    
                    if not gestion:
                        continue
                    
                    # Obtener comprobante y expandir rangos
                    col_comprobante = obtener_columna_mapeada(df, [
                        'NRO. COMPROBANTE DIARIO', 'NRO. DE COMPROBANTE DIARIO',
                        'NRO. PREVENTIVOS', 'PREVENTIVOS', 'ASIENTOS MANUALES',
                        'COMPROBANTE DE CONTABILIDAD TRASPASO',
                        'NRO INGRESO', 'NRO. INGRESO', 'NRO. CEPS'
                    ])
                    
                    if col_comprobante:
                        comprobantes = expandir_rango(fila[col_comprobante])
                    else:
                        comprobantes = [None]
                    
                    # Obtener código ABC
                    col_abc = obtener_columna_mapeada(df, ['ABC/1,1:1,2:1,3…'])
                    codigo_abc = limpiar_valor(fila[col_abc]) if col_abc else None
                    
                    # Obtener contenedor
                    col_contenedor = obtener_columna_mapeada(df, [
                        'NRO. LIBRO/AMARR', 'NRO. LIBRO AMARR', 
                        'NRO. LIBRO\nAMARR', 'NRO. LIBRO\nAMARRO'
                    ])
                    numero_contenedor = limpiar_valor(fila[col_contenedor]) if col_contenedor else None
                    
                    # Obtener bloque/nivel
                    col_bloque = obtener_columna_mapeada(df, ['BLOQUE/NIVEL', 'BLOQUE / NIVEL', 'BLOQUE\nNIVEL'])
                    bloque_nivel = limpiar_valor(fila[col_bloque]) if col_bloque else None
                    
                    # Obtener color
                    col_color = obtener_columna_mapeada(df, ['LIBRO COLOR', 'LIBRO\nCOLOR'])
                    color = limpiar_valor(fila[col_color]) if col_color else None
                    
                    # Obtener ubicación
                    col_ubicacion = obtener_columna_mapeada(df, ['Ubicación Unidad/Área'])
                    ubicacion = limpiar_valor(fila[col_ubicacion]) if col_ubicacion else None
                    
                    # Determinar tipo de contenedor (LIBRO si tiene color, sino AMARRO)
                    tipo_contenedor = 'LIBRO' if color else 'AMARRO'
                    
                    # Obtener o crear contenedor
                    contenedor_id = None
                    if numero_contenedor:
                        contenedor_id = obtener_o_crear_contenedor(
                            cursor, tipo_contenedor, numero_contenedor,
                            bloque_nivel, color, ubicacion
                        )
                        if contenedor_id and gestion:
                            actualizar_clasificacion(cursor, contenedor_id, tipo_documento, gestion)
                    
                    # Insertar documentos (uno por cada comprobante expandido)
                    for nro_comprobante in comprobantes:
                        if not nro_comprobante:
                            continue
                        
                        if tipo_documento == 'HOJA_RUTA_DIARIOS':
                            # Hojas de ruta van a su tabla específica
                            col_hr = obtener_columna_mapeada(df, ['NRO.\nHOJA DE RUTA', 'NRO. HOJA DE RUTA'])
                            nro_hr = limpiar_valor(fila[col_hr]) if col_hr else None
                            
                            col_conam = obtener_columna_mapeada(df, ['CONAM'])
                            conam = limpiar_valor(fila[col_conam]) if col_conam else None
                            
                            col_rubro = obtener_columna_mapeada(df, ['RUBRO'])
                            rubro = limpiar_valor(fila[col_rubro]) if col_rubro else None
                            
                            col_interesado = obtener_columna_mapeada(df, ['INTERESADO'])
                            interesado = limpiar_valor(fila[col_interesado]) if col_interesado else None
                            
                            sql = """
                                INSERT IGNORE INTO registro_hojas_ruta 
                                (gestion, nro_comprobante_diario, nro_hoja_ruta, conam, rubro, 
                                interesado, contenedor_fisico_id, activo)
                                VALUES (%s, %s, %s, %s, %s, %s, %s, 1)
                            """
                            cursor.execute(sql, (gestion, nro_comprobante, nro_hr, conam, rubro, interesado, contenedor_id))
                        else:
                            # Resto de documentos van a registro_diario
                            sql = """
                                INSERT IGNORE INTO registro_diario 
                                (gestion, nro_comprobante, codigo_abc, tipo_documento, 
                                contenedor_fisico_id, estado_documento)
                                VALUES (%s, %s, %s, %s, %s, 'DISPONIBLE')
                            """
                            cursor.execute(sql, (gestion, nro_comprobante, codigo_abc, tipo_documento, contenedor_id))
                        
                        total_insertados += 1
                
                except Exception as e:
                    errores += 1
                    if errores <= 5:  # Solo mostrar primeros 5 errores
                        print(f"⚠️  Error en fila {idx + 2}: {str(e)[:100]}")
                    continue
            
            connection.commit()
            print(f"✅ Insertados: {total_insertados} registros")
            if errores > 0:
                print(f"⚠️  Errores: {errores} filas (ver detalles arriba)")
    
    except Exception as e:
        connection.rollback()
        print(f"❌ ERROR durante importación: {e}")
    
    finally:
        connection.close()
    
    return total_insertados

# =====================================================
# SCRIPT PRINCIPAL
# =====================================================

if __name__ == "__main__":
    print("="*80)
    print("IMPORTACIÓN DE EXCEL A BASE DE DATOS - TAMEP")
    print("="*80)
    print(f"Hora inicio: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    total_general = 0
    
    for tipo_doc, archivo in EXCEL_FILES:
        total = importar_excel(tipo_doc, archivo)
        total_general += total
    
    print("\n" + "="*80)
    print(f"✅ IMPORTACIÓN COMPLETADA")
    print(f"Total de registros importados: {total_general}")
    print(f"Hora fin: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("="*80)
    print("\n📊 Ejecuta '04_verificar_importacion.sql' para verificar los datos")
