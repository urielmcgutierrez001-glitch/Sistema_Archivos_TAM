-- Verificar contenedores actuales
SELECT 
    'Contenedores Actuales' as info,
    COUNT(*) as total,
    tipo_contenedor,
    GROUP_CONCAT(DISTINCT numero ORDER BY numero SEPARATOR ', ') as numeros
FROM contenedores_fisicos
GROUP BY tipo_contenedor;

-- Ver todos los contenedores
SELECT * FROM contenedores_fisicos;

-- Verificar documentos sin contenedor
SELECT 
    'Documentos sin contenedor' as info,
    COUNT(*) as total
FROM registro_diario
WHERE contenedor_fisico_id IS NULL;

-- Ver muestra de documentos con contenedor
SELECT 
    rd.id,
    rd.gestion,
    rd.nro_comprobante,
    rd.contenedor_fisico_id,
    cf.tipo_contenedor,
    cf.numero
FROM registro_diario rd
LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
LIMIT 20;
