-- =====================================================
-- MIGRACIÓN TAMEP - PARTE 2: MIGRAR registro_egreso
-- Fecha: 2025-12-22
-- =====================================================

-- Paso 1: Agregar columna tabla_origen en registro_diario (si no existe)
-- Intentar agregar la columna (ignorar error si ya existe)
ALTER TABLE registro_diario 
ADD COLUMN tabla_origen VARCHAR(50) DEFAULT 'registro_diario';

-- Paso 2: Migrar datos de registro_egreso a registro_diario
-- IMPORTANTE: Esto copiará TODOS los registros de registro_egreso a registro_diario
-- Usamos INSERT IGNORE para evitar duplicados si ya existían
INSERT IGNORE INTO registro_diario (
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    tipo_documento,
    estado_documento,
    observaciones,
    tabla_origen,
    fecha_creacion,
    fecha_modificacion
)
SELECT 
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    'REGISTRO_CEPS' as tipo_documento,  -- Asignar tipo CEPS a todos
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        ELSE 'DISPONIBLE'
    END as estado_documento,
    observaciones,
    'registro_egreso' as tabla_origen,  -- Marcar origen
    fecha_creacion,
    fecha_modificacion
FROM registro_egreso
WHERE activo = 1;  -- Solo migrar registros activos

-- Paso 3: Verificar migración
SELECT 
    'registro_diario' as tabla,
    COUNT(*) as total,
    SUM(CASE WHEN tabla_origen = 'registro_egreso' THEN 1 ELSE 0 END) as migrados_de_egreso
FROM registro_diario

UNION ALL

SELECT 
    'registro_egreso' as tabla,
    COUNT(*) as total,
    NULL as migrados_de_egreso
FROM registro_egreso;

-- Paso 4: Ver algunos ejemplos de registros migrados
SELECT id, gestion, nro_comprobante, tipo_documento, estado_documento, tabla_origen
FROM registro_diario 
WHERE tabla_origen = 'registro_egreso'
LIMIT 10;
