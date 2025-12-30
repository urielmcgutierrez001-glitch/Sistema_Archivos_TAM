-- =====================================================
-- DIAGNÓSTICO DE CONTENEDORES FÍSICOS (AMARROS Y LIBROS)
-- =====================================================

-- 1. Ver distribución actual de contenedores por tipo
SELECT 
    'Distribución por Tipo' as categoria,
    tipo_contenedor,
    COUNT(*) as total
FROM contenedores_fisicos
GROUP BY tipo_contenedor;

-- 2. Ver contenedores agrupados por tipo de documento que contienen
SELECT 
    'Contenedores por Tipo de Documento' as categoria,
    cf.tipo_contenedor,
    rd.tipo_documento,
    COUNT(DISTINCT cf.id) as total_contenedores,
    COUNT(rd.id) as total_documentos,
    MIN(cf.numero) as primer_numero,
    MAX(cf.numero) as ultimo_numero
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE rd.tipo_documento IS NOT NULL
GROUP BY cf.tipo_contenedor, rd.tipo_documento
ORDER BY cf.tipo_contenedor, rd.tipo_documento;

-- 3. Ver contenedores con múltiples tipos de documentos (posible problema)
SELECT 
    'Contenedores Mixtos' as categoria,
    cf.id,
    cf.tipo_contenedor,
    cf.numero,
    GROUP_CONCAT(DISTINCT rd.tipo_documento) as tipos_documento,
    COUNT(DISTINCT rd.tipo_documento) as cantidad_tipos
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
GROUP BY cf.id, cf.tipo_contenedor, cf.numero
HAVING cantidad_tipos > 1
ORDER BY cantidad_tipos DESC;

-- 4. Ver numeración de contenedores por tipo
SELECT 
    'Numeración de Contenedores' as categoria,
    tipo_contenedor,
    numero,
    bloque_nivel,
    color,
    ubicacion_id
FROM contenedores_fisicos
ORDER BY tipo_contenedor, CAST(numero AS UNSIGNED);

-- 5. Detectar posibles números duplicados o problemáticos
SELECT 
    'Números Duplicados' as categoria,
    tipo_contenedor,
    numero,
    COUNT(*) as veces_usado
FROM contenedores_fisicos
GROUP BY tipo_contenedor, numero
HAVING veces_usado > 1
ORDER BY veces_usado DESC;

-- 6. Ver contenedores sin documentos asignados
SELECT 
    'Contenedores Vacíos' as categoria,
    cf.tipo_contenedor,
    cf.numero,
    cf.bloque_nivel,
    cf.color
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE rd.id IS NULL
ORDER BY cf.tipo_contenedor, CAST(cf.numero AS UNSIGNED);
