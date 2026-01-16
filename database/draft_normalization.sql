-- =============================================
-- MIGRACIÓN DE NORMALIZACIÓN DE TIPOS
-- =============================================

-- 1. ACTUALIZAR DOCUMENTOS (Llenar FK faltantes)
-- ---------------------------------------------
-- Mapear REGISTRO_DIARIO
UPDATE documentos 
SET tipo_documento_id = (SELECT id FROM tipo_documento WHERE codigo = 'REGISTRO_DIARIO' LIMIT 1)
WHERE tipo_documento = 'REGISTRO_DIARIO' AND tipo_documento_id IS NULL;

-- Mapear HOJA_RUTA_DIARIOS (Por si acaso quedó alguno null)
UPDATE documentos 
SET tipo_documento_id = (SELECT id FROM tipo_documento WHERE codigo = 'HOJA_RUTA_DIARIOS' LIMIT 1)
WHERE tipo_documento = 'HOJA_RUTA_DIARIOS' AND tipo_documento_id IS NULL;

-- 2. TRANSICIÓN EN CONTENEDORES FISICOS
-- ---------------------------------------------
-- Agregar columna
ALTER TABLE contenedores_fisicos 
ADD COLUMN tipo_documento_id INT NULL AFTER gestion;

-- Poblar columna basada en string actual
-- Asumimos mapeo: 'LIBRO' -> Libro de Diarios?, 'AMARRO' -> ?
-- ERROR POTENCIAL: 'tipo_documento' en contenedores puede ser 'LIBRO' o 'AMARRO' (que son TIPOS DE CONTENEDOR, no de documento?)
-- O se refiere a que el contenedor PERTENECE a un tipo de documento?
-- Revisar datos antes de ejecutar esto.

-- Si tipo_documento en contenedores es 'Registro Diario', mapear a ID de REGISTRO_DIARIO
UPDATE contenedores_fisicos c
JOIN tipo_documento t ON t.nombre = c.tipo_documento OR t.codigo = c.tipo_documento
SET c.tipo_documento_id = t.id;

-- 3. VALIDAR Y LIMPIAR
-- ---------------------------------------------
-- Verificar si quedaron nulos antes de borrar columnas
