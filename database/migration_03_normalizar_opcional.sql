-- =====================================================
-- MIGRACIÓN TAMEP - PARTE 3: ACTUALIZAR ESTRUCTURA
-- Fecha: 2025-12-22
-- =====================================================

-- OPCIONAL: Convertir tipo_documento a foreign key
-- Solo ejecutar si quieres normalización completa

-- Paso 1: Agregar nueva columna tipo_documento_id
ALTER TABLE registro_diario 
ADD COLUMN tipo_documento_id INT NULL AFTER tipo_documento;

-- Paso 2: Desactivar safe mode temporalmente
SET SQL_SAFE_UPDATES = 0;

-- Paso 3: Actualizar tipo_documento_id basado en tipo_documento actual
UPDATE registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento = td.codigo
SET rd.tipo_documento_id = td.id;

-- Paso 4: Reactivar safe mode
SET SQL_SAFE_UPDATES = 1;

-- Paso 3: Verificar que todos tengan tipo_documento_id
SELECT 
    tipo_documento,
    tipo_documento_id,
    COUNT(*) as total
FROM registro_diario
GROUP BY tipo_documento, tipo_documento_id;

-- Paso 4: Agregar foreign key constraint
ALTER TABLE registro_diario
ADD CONSTRAINT fk_registro_tipo_documento
FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento(id);

-- Paso 5: Crear índice para mejorar búsquedas
CREATE INDEX idx_tipo_documento_id ON registro_diario(tipo_documento_id);

-- NOTA: La columna 'tipo_documento' VARCHAR se mantiene por compatibilidad
-- Puedes eliminarla después de verificar que todo funciona:
-- ALTER TABLE registro_diario DROP COLUMN tipo_documento;
