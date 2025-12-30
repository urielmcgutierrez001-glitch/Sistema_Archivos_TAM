-- =====================================================
-- VERIFICACIÓN FINAL DE IMPORTACIÓN CORREGIDA
-- =====================================================

-- 1. Contenedores creados
SELECT 
    '1. Contenedores por Tipo' as info,
    tipo_contenedor,
    COUNT(*) as total,
    MIN(numero) as min_numero,
    MAX(numero) as max_numero
FROM contenedores_fisicos
GROUP BY tipo_contenedor;

-- 2. Documentos con contenedor
SELECT 
    '2. Documentos' as info,
    COUNT(*) as total_docs,
    COUNT(contenedor_fisico_id) as con_contenedor,
    COUNT(*) - COUNT(contenedor_fisico_id) as sin_contenedor,
    ROUND(COUNT(contenedor_fisico_id) * 100.0 / COUNT(*), 1) as porcentaje
FROM registro_diario;

-- 3. Ver distribución de documentos por contenedor
SELECT 
    '3. Top 10 Contenedores Más Llenos' as info,
    cf.tipo_contenedor,
    cf.numero,
    cf.bloque_nivel,
    cf.color,
    u.nombre as ubicacion,
    COUNT(rd.id) as total_docs
FROM contenedores_fisicos cf
LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
LEFT JOIN registro_diario rd ON rd.contenedor_fisico_id = cf.id
GROUP BY cf.id
ORDER BY total_docs DESC
LIMIT 10;

-- 4. Verificar datos completos (bloque, color, ubicación)
SELECT 
    '4. Campos Poblados en Contenedores' as info,
    COUNT(*) as total,
    COUNT(bloque_nivel) as con_bloque,
    COUNT(color) as con_color,
    COUNT(ubicacion_id) as con_ubicacion
FROM contenedores_fisicos;

-- 5. Códigos ABC capturados
SELECT 
    '5. Códigos ABC' as info,
    COUNT(*) as total_docs,
    COUNT(codigo_abc) as con_codigo_abc,
    COUNT(*) - COUNT(codigo_abc) as sin_codigo_abc
FROM registro_diario;

-- 6. Muestra de documentos correctamente importados
SELECT 
    rd.gestion,
    rd.nro_comprobante,
    rd.codigo_abc,
    td.nombre as tipo_doc,
    cf.tipo_contenedor,
    cf.numero,
    cf.bloque_nivel,
    cf.color,
    u.nombre as ubicacion
FROM registro_diario rd
INNER JOIN tipo_documento td ON rd.tipo_documento_id = td.id
LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
WHERE rd.gestion = 2007
LIMIT 20;

SELECT '✅ Verificación completada' as resultado;
