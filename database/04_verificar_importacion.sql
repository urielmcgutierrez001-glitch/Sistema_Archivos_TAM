-- =====================================================
-- VERIFICACIÓN POST-IMPORTACIÓN
-- Queries para verificar que los datos se importaron correctamente
-- =====================================================

-- 1. CONTEO GENERAL POR TIPO DE DOCUMENTO
SELECT 
    'Documentos por Tipo' as categoria,
    tipo_documento,
    COUNT(*) as total,
    MIN(gestion) as gestion_min,
    MAX(gestion) as gestion_max
FROM registro_diario
GROUP BY tipo_documento
ORDER BY total DESC;

-- 2. CONTEO DE HOJAS DE RUTA
SELECT 
    'Hojas de Ruta' as categoria,
    COUNT(*) as total,
    MIN(gestion) as gestion_min,
    MAX(gestion) as gestion_max
FROM registro_hojas_ruta;

-- 3. TOTAL GENERAL
SELECT 
    'TOTAL GENERAL' as info,
    (SELECT COUNT(*) FROM registro_diario) as total_diarios,
    (SELECT COUNT(*) FROM registro_hojas_ruta) as total_hojas_ruta,
    (SELECT COUNT(*) FROM registro_diario) + (SELECT COUNT(*) FROM registro_hojas_ruta) as gran_total;

-- 4. CONTENEDORES CREADOS
SELECT 
    'Contenedores Creados' as categoria,
    tipo_contenedor,
    COUNT(*) as total,
    MIN(numero) as primer_numero,
    MAX(numero) as ultimo_numero
FROM contenedores_fisicos
GROUP BY tipo_contenedor;

-- 5. CLASIFICACIÓN DE CONTENEDORES
SELECT 
    'Clasificaciones' as categoria,
    tipo_documento,
    COUNT(DISTINCT contenedor_id) as contenedores_clasificados,
    SUM(cantidad_documentos) as total_documentos_clasificados
FROM clasificacion_contenedor_documento
GROUP BY tipo_documento
ORDER BY total_documentos_clasificados DESC;

-- 6. CONTENEDORES SIN DOCUMENTOS (Debería ser 0)
SELECT 
    'Contenedores sin Docs (debería ser 0)' as alerta,
    COUNT(*) as total
FROM contenedores_fisicos cf
WHERE NOT EXISTS (
    SELECT 1 FROM registro_diario WHERE contenedor_fisico_id = cf.id
)
AND NOT EXISTS (
    SELECT 1 FROM registro_hojas_ruta WHERE contenedor_fisico_id = cf.id
);

-- 7. DOCUMENTOS POR AÑO
SELECT 
    'Documentos por Año' as categoria,
    gestion,
    COUNT(*) as total
FROM (
    SELECT gestion FROM registro_diario
    UNION ALL
    SELECT gestion FROM registro_hojas_ruta
) AS todos
GROUP BY gestion
ORDER BY gestion DESC;

-- 8. TOP 10 CONTENEDORES MÁS LLENOS
SELECT 
    'Top 10 Contenedores Más Llenos' as info,
    cf.tipo_contenedor,
    cf.numero,
    COUNT(rd.id) as total_documentos
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
GROUP BY cf.id, cf.tipo_contenedor, cf.numero
ORDER BY total_documentos DESC
LIMIT 10;

-- 9. VERIFICAR RANGOS EXPANDIDOS
-- Ver si hay documentos con números consecutivos (indica que se expandió un rango)
SELECT 
    'Rangos Expandidos Detectados' as info,
    tipo_documento,
    gestion,
    nro_comprobante,
    siguiente
FROM (
    SELECT 
        tipo_documento,
        gestion,
        nro_comprobante,
        LEAD(nro_comprobante) OVER (PARTITION BY tipo_documento, gestion ORDER BY nro_comprobante) as siguiente
    FROM registro_diario
    WHERE tipo_documento IS NOT NULL
) AS con_siguiente
WHERE siguiente = nro_comprobante + 1
LIMIT 20;

-- 10. RESUMEN FINAL
SELECT '==========================================' as separador
UNION ALL
SELECT 'RESUMEN FINAL DE IMPORTACIÓN'
UNION ALL
SELECT '==========================================' 
UNION ALL
SELECT CONCAT('Total Documentos: ', 
    (SELECT COUNT(*) FROM registro_diario) + (SELECT COUNT(*) FROM registro_hojas_ruta))
UNION ALL
SELECT CONCAT('Total Contenedores: ', (SELECT COUNT(*) FROM contenedores_fisicos))
UNION ALL
SELECT CONCAT('Total Clasificaciones: ', (SELECT COUNT(*) FROM clasificacion_contenedor_documento))
UNION ALL
SELECT '==========================================';
