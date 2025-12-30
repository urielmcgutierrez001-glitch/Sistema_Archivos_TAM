-- =====================================================
-- VERIFICACIÓN FINAL DE NORMALIZACIÓN
-- =====================================================

-- 1. Verificar tipo_documento_id poblado
SELECT 
    '1. tipo_documento_id' as verificacion,
    COUNT(*) as total_documentos,
    COUNT(tipo_documento_id) as con_tipo_id,
    COUNT(*) - COUNT(tipo_documento_id) as faltantes
FROM registro_diario;
-- faltantes debe ser 0

-- 2. Ver distribución por tipo
SELECT 
    '2. Distribución por Tipo' as verificacion,
    td.codigo,
    td.nombre,
    COUNT(*) as total_docs
FROM registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento_id = td.id
GROUP BY td.id, td.codigo, td.nombre
ORDER BY total_docs DESC;

-- 3. Verificar FK de tipo_documento
SELECT 
    '3. Foreign Keys' as verificacion,
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'registro_diario'
AND REFERENCED_TABLE_NAME = 'tipo_documento';

-- 4. Verificar estructura de contenedores
SELECT 
    '4. Contenedores - Estructura' as verificacion,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'contenedores_fisicos'
AND COLUMN_NAME IN ('tipo_contenedor', 'tipo_contenedor_id', 'ubicacion_id')
ORDER BY ORDINAL_POSITION;

-- 5. Verificar que tipo_contenedor_id fue eliminado
SELECT 
    '5. Columna tipo_contenedor_id' as verificacion,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ Eliminada correctamente'
        ELSE '❌ Aún existe'
    END as estado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'contenedores_fisicos'
AND COLUMN_NAME = 'tipo_contenedor_id';

-- 6. Verificar constraint de tipo_contenedor
SELECT 
    '6. Constraint tipo_contenedor' as verificacion,
    CONSTRAINT_NAME,
    CHECK_CLAUSE
FROM information_schema.CHECK_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
AND CONSTRAINT_NAME = 'chk_tipo_contenedor';

-- 7. Estadísticas de ubicaciones
SELECT 
    '7. Ubicaciones en Contenedores' as verificacion,
    COUNT(*) as total,
    COUNT(ubicacion_id) as con_ubicacion,
    COUNT(*) - COUNT(ubicacion_id) as sin_ubicacion,
    ROUND(COUNT(ubicacion_id) * 100.0 / COUNT(*), 2) as porcentaje_con_ubicacion
FROM contenedores_fisicos;

-- 8. Ver ubicaciones más usadas
SELECT 
    '8. Top Ubicaciones' as verificacion,
    u.nombre as ubicacion,
    COUNT(*) as total_contenedores
FROM contenedores_fisicos cf
INNER JOIN ubicaciones u ON cf.ubicacion_id = u.id
GROUP BY u.id, u.nombre
ORDER BY total_contenedores DESC;

-- 9. Verificar ENUM vs FK
SELECT 
    '9. Comparación ENUM vs FK' as verificacion,
    'tipo_documento ENUM' as campo,
    COUNT(*) as total
FROM registro_diario
WHERE tipo_documento IS NOT NULL
UNION ALL
SELECT 
    '9. Comparación ENUM vs FK',
    'tipo_documento_id FK',
    COUNT(*)
FROM registro_diario
WHERE tipo_documento_id IS NOT NULL;

-- 10. Resumen Final
SELECT 
    '========================================' as separador
UNION ALL
SELECT 'RESUMEN DE NORMALIZACIÓN'
UNION ALL
SELECT '========================================'
UNION ALL
SELECT CONCAT('✅ Documentos con tipo_documento_id: ', 
    (SELECT COUNT(*) FROM registro_diario WHERE tipo_documento_id IS NOT NULL))
UNION ALL
SELECT CONCAT('✅ Tipos de documento en catálogo: ', 
    (SELECT COUNT(*) FROM tipo_documento))
UNION ALL
SELECT CONCAT('✅ Contenedores totales: ', 
    (SELECT COUNT(*) FROM contenedores_fisicos))
UNION ALL
SELECT CONCAT('✅ Contenedores con ubicación: ', 
    (SELECT COUNT(*) FROM contenedores_fisicos WHERE ubicacion_id IS NOT NULL))
UNION ALL
SELECT '========================================';
