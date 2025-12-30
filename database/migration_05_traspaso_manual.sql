-- =====================================================
-- MIGRACIÓN MANUAL - registro_traspaso
-- =====================================================

-- PASO 1: Ver la estructura de la tabla
DESCRIBE registro_traspaso;

-- PASO 2: Ver algunos registros de ejemplo
SELECT * FROM registro_traspaso LIMIT 5;

-- PASO 3: Contar registros totales
SELECT COUNT(*) as total FROM registro_traspaso;

-- PASO 4: Verificar si existe columna 'activo'
SELECT COUNT(*) as total_activos 
FROM registro_traspaso 
WHERE activo = 1;

-- PASO 5: Si las consultas anteriores funcionan, ejecutar esta migración:
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
    'DISPONIBLE' as estado_documento,  -- Por defecto DISPONIBLE
    observaciones,
    'registro_traspaso' as tabla_origen,
    CURRENT_TIMESTAMP as fecha_creacion,
    CURRENT_TIMESTAMP as fecha_modificacion
FROM registro_traspaso;

-- PASO 6: Si la tabla tiene campo estado_perdido, usar este en su lugar:
/*
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
WHERE activo = 1;
*/

-- PASO 7: Actualizar tipo_documento_id
SET SQL_SAFE_UPDATES = 0;

UPDATE registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento = td.codigo
SET rd.tipo_documento_id = td.id
WHERE rd.tipo_documento_id IS NULL;

SET SQL_SAFE_UPDATES = 1;

-- PASO 8: Verificar migración
SELECT COUNT(*) as total_migrado 
FROM registro_diario 
WHERE tipo_documento = 'REGISTRO_TRASPASO';
