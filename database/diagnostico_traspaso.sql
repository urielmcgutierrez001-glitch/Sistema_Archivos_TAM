-- =====================================================
-- DIAGNÓSTICO: ¿Por qué no se migraron todos los registros?
-- =====================================================

-- 1. Ver cuántos registros de traspaso YA existían en registro_diario
SELECT COUNT(*) as ya_existian
FROM registro_traspaso rt
INNER JOIN registro_diario rd 
    ON rt.gestion = rd.gestion 
    AND rt.nro_comprobante = rd.nro_comprobante
WHERE rd.tipo_documento != 'REGISTRO_TRASPASO';

-- 2. Ver qué tipo de documentos son esos duplicados
SELECT 
    rd.tipo_documento,
    COUNT(*) as duplicados
FROM registro_traspaso rt
INNER JOIN registro_diario rd 
    ON rt.gestion = rd.gestion 
    AND rt.nro_comprobante = rd.nro_comprobante
WHERE rd.tipo_documento != 'REGISTRO_TRASPASO'
GROUP BY rd.tipo_documento;

-- 3. Ver algunos ejemplos de duplicados
SELECT 
    rt.id as traspaso_id,
    rt.gestion,
    rt.nro_comprobante,
    rd.tipo_documento as tipo_en_diario,
    rd.tabla_origen
FROM registro_traspaso rt
INNER JOIN registro_diario rd 
    ON rt.gestion = rd.gestion 
    AND rt.nro_comprobante = rd.nro_comprobante
WHERE rd.tipo_documento != 'REGISTRO_TRASPASO'
LIMIT 10;

-- 4. Ver registros de traspaso que NO están en registro_diario
SELECT COUNT(*) as no_migrados
FROM registro_traspaso rt
WHERE NOT EXISTS (
    SELECT 1 FROM registro_diario rd 
    WHERE rd.gestion = rt.gestion 
    AND rd.nro_comprobante = rt.nro_comprobante
);

-- 5. Si quieres migrar FORZANDO (actualizar los existentes a TRASPASO):
-- PRECAUCIÓN: Esto cambiará el tipo de los documentos que ya existen
/*
UPDATE registro_diario rd
INNER JOIN registro_traspaso rt 
    ON rd.gestion = rt.gestion 
    AND rd.nro_comprobante = rt.nro_comprobante
SET 
    rd.tipo_documento = 'REGISTRO_TRASPASO',
    rd.tipo_documento_id = 7,
    rd.tabla_origen = 'registro_traspaso'
WHERE rd.tipo_documento != 'REGISTRO_TRASPASO';
*/
