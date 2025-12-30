-- Permitir NULL en ubicacion_id
ALTER TABLE contenedores_fisicos
MODIFY COLUMN ubicacion_id INT NULL;

-- Verificar cambio
DESC contenedores_fisicos;

SELECT '✅ ubicacion_id ahora permite NULL' as resultado;
