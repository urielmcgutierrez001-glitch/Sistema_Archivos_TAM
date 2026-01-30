-- Script DIRECTO para agregar columna y FK
-- Ejecuta línea por línea o todo el bloque. Si falla, el error nos dirá por qué.

-- 1. Agregar la columna (si dice "Duplicate column name", ya existe)
ALTER TABLE prestamos ADD COLUMN estado_anterior_id INT NULL AFTER observacion_devolucion;

-- 2. Agregar la Foreign Key (si dice "Duplicate key name" o error de tipos, nos avisará)
ALTER TABLE prestamos ADD CONSTRAINT fk_prestamos_estado_anterior 
FOREIGN KEY (estado_anterior_id) REFERENCES estados(id);

-- 3. Verificación
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'prestamos' AND COLUMN_NAME = 'estado_anterior_id';
