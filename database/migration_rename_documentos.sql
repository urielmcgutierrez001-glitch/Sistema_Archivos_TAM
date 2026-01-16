-- =====================================================
-- MIGRACIÓN IMPORTANTE: RENOMBRAR REGISTRO_DIARIO Y AGREGAR ATRIBUTOS
-- =====================================================

-- 1. Renombrar tabla principal
RENAME TABLE registro_diario TO documentos;

-- 2. Agregar columnas para atributos dinámicos
-- Usamos TEXT para máxima compatibilidad, pero almacenaremos JSON
ALTER TABLE documentos ADD COLUMN atributos_extra TEXT COMMENT 'JSON con campos variables';
ALTER TABLE tipo_documento ADD COLUMN esquema_atributos TEXT COMMENT 'JSON con definición de campos visibles y extras';

-- 3. Crear índices de alto rendimiento
-- Indice compuesto para búsquedas rápidas por Tipo y Número
CREATE INDEX idx_tipo_nro ON documentos (tipo_documento_id, nro_comprobante);

-- Indice para búsquedas rápidas por Gestión (Año)
CREATE INDEX idx_gestion ON documentos (gestion);

-- 4. Actualizar tabla origen (si existe esa columna para rastreo)
UPDATE documentos SET tabla_origen = 'documentos' WHERE tabla_origen = 'registro_diario';

SELECT 'MIGRACION COMPLETADA EXITOSAMENTE' as resultado;
