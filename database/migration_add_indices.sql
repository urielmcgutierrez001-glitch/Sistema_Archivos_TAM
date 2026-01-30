-- Script de Optimización para evitar "No space left on device"
-- El error ocurre porque MySQL intenta ordenar 75,000 registros sin índice, usando el disco duro (/tmp).
-- Al agregar estos índices, el ordenamiento se hace en memoria.

-- 1. Índice para búsquedas y ordenamiento por gestión y comprobante (Lo más usado)
CREATE INDEX idx_documentos_gestion_nro ON documentos(gestion, nro_comprobante);

-- 2. Índice para filtrar por estado (para saber qué está disponible o prestado)
-- Verifica si tu columna se llama 'estado_documento_id' o 'estado_documento'
-- Si usas la columna antigua (VARCHAR):
-- CREATE INDEX idx_documentos_estado ON documentos(estado_documento);
-- Si usas la columna nueva (ID):
CREATE INDEX idx_documentos_estado_id ON documentos(estado_documento_id);

-- 3. Índice combinado para la búsqueda "getAvailable" típica
CREATE INDEX idx_docs_disponibles ON documentos(estado_documento_id, gestion, nro_comprobante);

SELECT "Indices creados correctamente. La búsqueda ahora será instantánea." as Mensaje;
