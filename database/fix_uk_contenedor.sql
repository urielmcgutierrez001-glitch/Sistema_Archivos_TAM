-- Script para corregir el constraint único en contenedores_fisicos
-- El constraint debe incluir tipo_documento para permitir:
-- AMARRO-3-REGISTRO_CEPS vs AMARRO-3-REGISTRO_DIARIO

-- Ver constraint actual
SHOW CREATE TABLE contenedores_fisicos;

-- Eliminar constraint antiguo
ALTER TABLE contenedores_fisicos DROP INDEX uk_contenedor;

-- Crear nuevo constraint que incluye tipo_documento
ALTER TABLE contenedores_fisicos 
ADD CONSTRAINT uk_contenedor_tipo_doc 
UNIQUE (tipo_contenedor, numero, tipo_documento);

-- Verificar
SHOW INDEX FROM contenedores_fisicos;
