-- =====================================================
-- MIGRACIÓN TAMEP - PARTE 4 (REVISADA): MIGRAR TABLAS ADICIONALES
-- Fecha: 2025-12-22
-- Este script verifica la existencia de tablas antes de migrar
-- =====================================================

-- PASO 1: Migrar registro_diarios_apertura (SI EXISTE)
-- Tipo: DIARIOS_APERTURA (id=6 en tipo_documento)
INSERT IGNORE INTO registro_diario (
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    tipo_documento,
    estado_documento,
    observaciones,
    tabla_origen,
    fecha_creacion,
    fecha_modificacion
)
SELECT 
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    'DIARIOS_APERTURA' as tipo_documento,
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        ELSE 'DISPONIBLE'
    END as estado_documento,
    observaciones,
    'registro_diarios_apertura' as tabla_origen,
    fecha_creacion,
    fecha_modificacion
FROM registro_diarios_apertura
WHERE EXISTS (SELECT 1 FROM information_schema.tables 
              WHERE table_schema = 'tamep_archivos' 
              AND table_name = 'registro_diarios_apertura')
AND activo = 1;

-- PASO 2: Migrar registro_ingreso (SI EXISTE) - NOTA: nombre singular
-- Tipo: REGISTRO_INGRESO (id=2 en tipo_documento)
INSERT IGNORE INTO registro_diario (
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    tipo_documento,
    estado_documento,
    observaciones,
    tabla_origen,
    fecha_creacion,
    fecha_modificacion
)
SELECT 
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    'REGISTRO_INGRESO' as tipo_documento,
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        ELSE 'DISPONIBLE'
    END as estado_documento,
    observaciones,
    'registro_ingreso' as tabla_origen,
    fecha_creacion,
    fecha_modificacion
FROM registro_ingreso
WHERE EXISTS (SELECT 1 FROM information_schema.tables 
              WHERE table_schema = 'tamep_archivos' 
              AND table_name = 'registro_ingreso')
AND activo = 1;

-- PASO 3: Migrar registro_preventivos (SI EXISTE)
-- Tipo: PREVENTIVOS (id=4 en tipo_documento)
INSERT IGNORE INTO registro_diario (
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    tipo_documento,
    estado_documento,
    observaciones,
    tabla_origen,
    fecha_creacion,
    fecha_modificacion
)
SELECT 
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    'PREVENTIVOS' as tipo_documento,
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        ELSE 'DISPONIBLE'
    END as estado_documento,
    observaciones,
    'registro_preventivos' as tabla_origen,
    fecha_creacion,
    fecha_modificacion
FROM registro_preventivos
WHERE EXISTS (SELECT 1 FROM information_schema.tables 
              WHERE table_schema = 'tamep_archivos' 
              AND table_name = 'registro_preventivos')
AND activo = 1;

-- PASO 4: Migrar registro_traspaso (SI EXISTE)
-- Tipo: REGISTRO_TRASPASO (id=7 en tipo_documento)
INSERT IGNORE INTO registro_diario (
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    tipo_documento,
    estado_documento,
    observaciones,
    tabla_origen,
    fecha_creacion,
    fecha_modificacion
)
SELECT 
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    'REGISTRO_TRASPASO' as tipo_documento,
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        ELSE 'DISPONIBLE'
    END as estado_documento,
    observaciones,
    'registro_traspaso' as tabla_origen,
    fecha_creacion,
    fecha_modificacion
FROM registro_traspaso
WHERE EXISTS (SELECT 1 FROM information_schema.tables 
              WHERE table_schema = 'tamep_archivos' 
              AND table_name = 'registro_traspaso')
AND activo = 1;

-- PASO 5: Actualizar tipo_documento_id para las nuevas migraciones
-- Desactivar safe mode temporalmente
SET SQL_SAFE_UPDATES = 0;

UPDATE registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento = td.codigo
SET rd.tipo_documento_id = td.id
WHERE rd.tipo_documento_id IS NULL;

-- Reactivar safe mode
SET SQL_SAFE_UPDATES = 1;

-- PASO 6: Verificar resultados
SELECT 
    tipo_documento,
    tabla_origen,
    COUNT(*) as total
FROM registro_diario
WHERE tabla_origen IN (
    'registro_diarios_apertura',
    'registro_ingreso', 
    'registro_preventivos',
    'registro_traspaso',
    'registro_egreso'
)
GROUP BY tipo_documento, tabla_origen
ORDER BY tipo_documento;

-- PASO 7: Ver totales generales por tipo
SELECT 
    tipo_documento,
    COUNT(*) as total_registros
FROM registro_diario
GROUP BY tipo_documento
ORDER BY tipo_documento;
