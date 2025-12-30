-- =====================================================
-- ELIMINAR REDUNDANCIA: tipo_contenedor vs tipo_contenedor_id
-- =====================================================
-- Decisión: Mantener tipo_contenedor (VARCHAR) porque es más directo
-- Eliminar tipo_contenedor_id y la FK a tipos_contenedor

SET SQL_SAFE_UPDATES = 0;

-- 1. Verificar estado actual
SELECT 
    'Estado Actual' as info,
    COUNT(*) as total_contenedores,
    COUNT(CASE WHEN tipo_contenedor IS NOT NULL THEN 1 END) as con_tipo_varchar,
    COUNT(CASE WHEN tipo_contenedor_id IS NOT NULL THEN 1 END) as con_tipo_id
FROM contenedores_fisicos;

-- 2. Verificar si hay discrepancias
SELECT 
    'Discrepancias' as alerta,
    cf.id,
    cf.tipo_contenedor,
    cf.tipo_contenedor_id,
    tc.codigo as codigo_desde_id
FROM contenedores_fisicos cf
LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
WHERE cf.tipo_contenedor != tc.codigo OR cf.tipo_contenedor_id IS NULL
LIMIT 10;

-- 3. Eliminar FK primero
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'contenedores_fisicos'
    AND CONSTRAINT_NAME = 'fk_tipo_contenedor'
);

SET @sql_drop_fk = IF(@fk_exists > 0,
    'ALTER TABLE contenedores_fisicos DROP FOREIGN KEY fk_tipo_contenedor',
    'SELECT "FK no existe" as msg');
PREPARE stmt FROM @sql_drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'FK eliminada' as paso;

-- 4. Eliminar columna tipo_contenedor_id
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contenedores_fisicos' 
    AND COLUMN_NAME = 'tipo_contenedor_id'
);

SET @sql_drop_col = IF(@column_exists > 0,
    'ALTER TABLE contenedores_fisicos DROP COLUMN tipo_contenedor_id',
    'SELECT "Columna no existe" as msg');
PREPARE stmt FROM @sql_drop_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Columna eliminada' as paso;

-- 5. Opcional: Eliminar tabla tipos_contenedor si no se usa
-- DESCOMENTA si quieres eliminarla
/*
DROP TABLE IF EXISTS tipos_contenedor;
SELECT 'Tabla tipos_contenedor eliminada' as paso;
*/

-- 6. Verificar estructura final
DESC contenedores_fisicos;

SELECT '✅ Redundancia eliminada - Solo queda tipo_contenedor (VARCHAR)' as resultado;

SET SQL_SAFE_UPDATES = 1;
