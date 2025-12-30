-- =====================================================
-- NORMALIZACIÓN PASO 1: Poblar tipo_documento_id
-- =====================================================
-- La tabla tipo_documento YA EXISTE
-- Solo necesitamos poblar tipo_documento_id desde el ENUM

SET SQL_SAFE_UPDATES = 0;

-- 1. Verificar tabla tipo_documento existente
SELECT * FROM tipo_documento ORDER BY orden;

-- 2. Poblar tipo_documento_id desde ENUM
UPDATE registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento = td.codigo
SET rd.tipo_documento_id = td.id;

-- 3. Verificar migración
SELECT 
    'Verificación de Migración' as paso,
    COUNT(*) as total_documentos,
    COUNT(tipo_documento_id) as con_tipo_id,
    COUNT(*) - COUNT(tipo_documento_id) as faltantes
FROM registro_diario;

-- Faltantes debe ser 0

-- 4. Ver distribución
SELECT 
    td.nombre as tipo,
    COUNT(*) as total
FROM registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento_id = td.id
GROUP BY td.id, td.nombre
ORDER BY total DESC;

-- 5. Agregar FK si no existe
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'registro_diario'
    AND CONSTRAINT_NAME LIKE '%tipo_documento_id%'
);

SET @sql_add_fk = IF(@fk_exists = 0,
    'ALTER TABLE registro_diario ADD FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento(id)',
    'SELECT "FK ya existe" as msg');
PREPARE stmt FROM @sql_add_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. (OPCIONAL) Eliminar ENUM después de verificar
-- DESCOMENTAR solo cuando estés seguro que todo funciona
/*
ALTER TABLE registro_diario
MODIFY COLUMN tipo_documento VARCHAR(50) NULL;

-- O eliminar completamente:
-- ALTER TABLE registro_diario DROP COLUMN tipo_documento;
*/

SELECT '✅ tipo_documento_id poblado correctamente' as resultado;

SET SQL_SAFE_UPDATES = 1;
