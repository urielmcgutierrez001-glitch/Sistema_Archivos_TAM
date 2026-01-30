-- Script de Diagnóstico PROFUNDO
-- Ejecuta esto para ver detalles técnicos de las tablas

SELECT 
    TABLE_NAME, 
    ENGINE, 
    TABLE_COLLATION 
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('estados', 'prestamos');

-- También verificar el tipo exacto de la columna ID en estados
SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    EXTRA 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'estados' 
AND COLUMN_NAME = 'id';
