--=====================================================
-- NORMALIZACIÓN PASO 3: Eliminar Redundancias
-- =====================================================

SET SQL_SAFE_UPDATES = 0;

-- 1. Eliminar tipo_contenedor_id (redundante con tipo_contenedor VARCHAR)
-- Primero eliminar FK
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

-- Eliminar columna
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

SELECT '✅ tipo_contenedor_id eliminado' as paso;

-- 2. Agregar constraint de validación a tipo_contenedor
ALTER TABLE contenedores_fisicos
DROP CONSTRAINT IF EXISTS chk_tipo_contenedor;

ALTER TABLE contenedores_fisicos
ADD CONSTRAINT chk_tipo_contenedor 
CHECK (tipo_contenedor IN ('AMARRO', 'LIBRO'));

SELECT '✅ Constraint agregado a tipo_contenedor' as paso;

-- 3. Verificar estructura final
DESC contenedores_fisicos;

-- 4. Eliminar tabla tipos_contenedor si existe y no se usa
-- PRECAUCIÓN: Solo si no hay otras referencias
/*
DROP TABLE IF EXISTS tipos_contenedor;
SELECT '✅ Tabla tipos_contenedor eliminada' as paso;
*/

-- 5. Verificar integridad
SELECT 
    'Verificación Final' as info,
    COUNT(*) as total_contenedores,
    COUNT(CASE WHEN ubicacion_id IS NOT NULL THEN 1 END) as con_ubicacion,
    COUNT(CASE WHEN ubicacion_id IS NULL THEN 1 END) as sin_ubicacion
FROM contenedores_fisicos;

SELECT '✅ Redundancias eliminadas correctamente' as resultado;

SET SQL_SAFE_UPDATES = 1;
