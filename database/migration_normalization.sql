-- =============================================
-- MIGRACIÓN DE NORMALIZACIÓN DE TIPOS
-- =============================================

-- 1. ACTUALIZAR DOCUMENTOS (Llenar FK faltantes)
-- ---------------------------------------------
-- Mapear REGISTRO_DIARIO
UPDATE documentos 
SET tipo_documento_id = (SELECT id FROM tipo_documento WHERE codigo = 'REGISTRO_DIARIO' LIMIT 1)
WHERE (tipo_documento = 'REGISTRO_DIARIO') AND tipo_documento_id IS NULL;

-- Mapear HOJA_RUTA_DIARIOS
UPDATE documentos 
SET tipo_documento_id = (SELECT id FROM tipo_documento WHERE codigo = 'HOJA_RUTA_DIARIOS' LIMIT 1)
WHERE (tipo_documento = 'HOJA_RUTA_DIARIOS') AND tipo_documento_id IS NULL;

-- 2. TRANSICIÓN EN CONTENEDORES FISICOS
-- ---------------------------------------------
-- Agregar columna si no existe (MariaDB ignore error if exists or use procedure, but simple ADD implies we expect it not to be there. 
-- User said "change A to B", usually implies existing structure. 
-- We will Add ID, Fill it, then Drop String.

ALTER TABLE contenedores_fisicos ADD COLUMN tipo_documento_id INT NULL;

-- Mapear string actual a ID
-- Intentamos coincidencia por codigo o nombre
UPDATE contenedores_fisicos c
JOIN tipo_documento t ON t.codigo = c.tipo_documento OR t.nombre = c.tipo_documento
SET c.tipo_documento_id = t.id;

-- Agregar FK
ALTER TABLE contenedores_fisicos
ADD CONSTRAINT fk_cont_tipo_doc
FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento(id);

-- 3. LIMPIEZA Opcional (Comentado por seguridad, se puede ejecutar luego)
-- ALTER TABLE documentos DROP COLUMN tipo_documento;
-- ALTER TABLE contenedores_fisicos DROP COLUMN tipo_documento;
