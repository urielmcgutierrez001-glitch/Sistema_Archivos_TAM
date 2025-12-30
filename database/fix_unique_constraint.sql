-- =====================================================
-- FIX: Modificar restricción UNIQUE para permitir múltiples tipos
-- =====================================================

-- PASO 1: Eliminar la restricción UNIQUE antigua
ALTER TABLE registro_diario DROP INDEX uk_diario;

-- PASO 2: Crear nueva restricción UNIQUE que incluya tipo_documento
ALTER TABLE registro_diario 
ADD UNIQUE KEY uk_diario (gestion, nro_comprobante, tipo_documento);

-- PASO 3: Re-migrar todos los registros de traspaso
INSERT IGNORE INTO registro_diario (
    gestion,
    nro_comprobante,
    codigo_abc,
    contenedor_fisico_id,
    tipo_documento,
    tipo_documento_id,
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
    'REGISTRO_TRASPASO' as tipo_documento,
    7 as tipo_documento_id,
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        ELSE 'DISPONIBLE'
    END as estado_documento,
    observaciones,
    'registro_traspaso' as tabla_origen,
    fecha_creacion,
    fecha_modificacion
FROM registro_traspaso
WHERE activo = 1;

-- PASO 4: Verificar que se migraron TODOS
SELECT 
    'Total en registro_traspaso' as tabla,
    COUNT(*) as total
FROM registro_traspaso
WHERE activo = 1

UNION ALL

SELECT 
    'Total migrado a registro_diario' as tabla,
    COUNT(*) as total
FROM registro_diario 
WHERE tipo_documento = 'REGISTRO_TRASPASO';

-- PASO 5: Ver resumen final de todos los tipos
SELECT 
    tipo_documento,
    COUNT(*) as total
FROM registro_diario
GROUP BY tipo_documento
ORDER BY tipo_documento;
