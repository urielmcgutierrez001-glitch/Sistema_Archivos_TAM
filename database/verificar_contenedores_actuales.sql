-- Verificar contenedores creados actualmente
SELECT 
    'Muestra de Contenedores' as info,
    id,
    tipo_contenedor,
    tipo_contenedor_id,
    numero,
    bloque_nivel,
    color,
    ubicacion_id
FROM contenedores_fisicos
LIMIT 20;

-- Contar por tipo
SELECT 
    'Por Tipo' as categoria,
    tipo_contenedor,
    COUNT(*) as total,
    COUNT(CASE WHEN bloque_nivel IS NOT NULL THEN 1 END) as con_bloque,
    COUNT(CASE WHEN color IS NOT NULL THEN 1 END) as con_color
FROM contenedores_fisicos
GROUP BY tipo_contenedor;

-- Ver si hay formato L-1
SELECT 
    'Formato L-N detectado' as info,
    numero
FROM contenedores_fisicos
WHERE numero LIKE 'L%'
LIMIT 10;
