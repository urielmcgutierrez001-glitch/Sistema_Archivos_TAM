-- =====================================================
-- PLAN DE ACCIÓN COMPLETO
-- Organización de Contenedores por Tipo de Documento
-- =====================================================

-- PASO 1: DIAGNÓSTICO INICIAL
-- =====================================================
-- Ejecuta estos queries primero para entender el problema

-- 1.1 ¿Cuántos contenedores mixtos hay?
SELECT 
    COUNT(DISTINCT cf.id) as total_contenedores_mixtos,
    SUM(doc_count.total) as total_documentos_afectados
FROM contenedores_fisicos cf
INNER JOIN (
    SELECT 
        contenedor_fisico_id,
        COUNT(*) as total
    FROM registro_diario
    GROUP BY contenedor_fisico_id
) doc_count ON cf.id = doc_count.contenedor_fisico_id
WHERE cf.id IN (
    SELECT contenedor_fisico_id
    FROM registro_diario
    GROUP BY contenedor_fisico_id
    HAVING COUNT(DISTINCT tipo_documento) > 1
);

-- 1.2 Listar todos los contenedores mixtos con detalle
SELECT 
    cf.id,
    cf.tipo_contenedor,
    cf.numero,
    GROUP_CONCAT(DISTINCT rd.tipo_documento) as tipos_mezclados,
    COUNT(DISTINCT rd.tipo_documento) as tipos_diferentes,
    COUNT(rd.id) as total_docs
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
GROUP BY cf.id, cf.tipo_contenedor, cf.numero
HAVING tipos_diferentes > 1
ORDER BY tipos_diferentes DESC, total_docs DESC;

-- PASO 2: DECISIÓN DE ESTRATEGIA
-- =====================================================
-- Elige UNA de estas estrategias:

-- ESTRATEGIA A: Separar por tipo predominante
-- El tipo con más documentos se queda, los demás se mueven a nuevos contenedores
-- Ventaja: Mantiene la mayoría de asignaciones intactas
-- Desventaja: Crea nuevos contenedores

-- ESTRATEGIA B: Reorganización completa por tipo
-- Todos los documentos se reasignan a contenedores dedicados por tipo
-- Ventaja: Organización perfecta desde cero
-- Desventaja: Cambia muchas asignaciones

-- ESTRATEGIA C: Manual caso por caso
-- Revisa cada contenedor mixto y decides qué hacer
-- Ventaja: Control total
-- Desventaja: Más trabajo manual

-- PASO 3: APLICAR CORRECCIÓN (ESTRATEGIA A - RECOMENDADA)
-- =====================================================

-- 3.1 Ver preview de qué se va a crear
SELECT 
    cf.id as contenedor_original_id,
    cf.tipo_contenedor,
    cf.numero as numero_original,
    rd.tipo_documento as tipo_minoritario,
    COUNT(*) as docs_a_mover,
    CONCAT(
        cf.numero, 
        '-', 
        CASE rd.tipo_documento
            WHEN 'REGISTRO_DIARIO' THEN 'DIARIO'
            WHEN 'REGISTRO_INGRESO' THEN 'INGRESO'
            WHEN 'REGISTRO_CEPS' THEN 'CEPS'
            WHEN 'PREVENTIVOS' THEN 'PREV'
            ELSE SUBSTRING(rd.tipo_documento, 1, 3)
        END
    ) as nuevo_contenedor_numero
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.id IN (
    SELECT contenedor_fisico_id
    FROM registro_diario
    GROUP BY contenedor_fisico_id
    HAVING COUNT(DISTINCT tipo_documento) > 1
)
GROUP BY cf.id, cf.tipo_contenedor, cf.numero, rd.tipo_documento
-- Excluir el tipo predominante
HAVING rd.tipo_documento != (
    SELECT tipo_documento
    FROM registro_diario
    WHERE contenedor_fisico_id = cf.id
    GROUP BY tipo_documento
    ORDER BY COUNT(*) DESC
    LIMIT 1
)
ORDER BY cf.numero;

-- 3.2 EJECUTAR CORRECCIÓN (Descomenta para aplicar)
/*
-- Backup primero
CREATE TABLE contenedores_fisicos_backup AS SELECT * FROM contenedores_fisicos;
CREATE TABLE registro_diario_backup AS SELECT * FROM registro_diario;

-- Crear nuevos contenedores
INSERT INTO contenedores_fisicos (tipo_contenedor, numero, bloque_nivel, ubicacion_id, activo)
SELECT DISTINCT
    cf.tipo_contenedor,
    CONCAT(
        cf.numero, 
        '-', 
        CASE rd.tipo_documento
            WHEN 'REGISTRO_DIARIO' THEN 'DIARIO'
            WHEN 'REGISTRO_INGRESO' THEN 'INGRESO'
            WHEN 'REGISTRO_CEPS' THEN 'CEPS'
            WHEN 'PREVENTIVOS' THEN 'PREV'
            WHEN 'ASIENTOS_MANUALES' THEN 'ASIENTO'
            WHEN 'DIARIOS_APERTURA' THEN 'APERTURA'
            WHEN 'REGISTRO_TRASPASO' THEN 'TRASPASO'
            WHEN 'HOJA_RUTA_DIARIOS' THEN 'HR'
            ELSE SUBSTRING(rd.tipo_documento, 1, 3)
        END
    ) as nuevo_numero,
    cf.bloque_nivel,
    cf.ubicacion_id,
    1
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.id IN (
    SELECT contenedor_fisico_id
    FROM registro_diario
    GROUP BY contenedor_fisico_id
    HAVING COUNT(DISTINCT tipo_documento) > 1
)
GROUP BY cf.id, cf.tipo_contenedor, cf.numero, rd.tipo_documento, cf.bloque_nivel, cf.ubicacion_id
HAVING rd.tipo_documento != (
    SELECT tipo_documento
    FROM registro_diario rd2
    WHERE rd2.contenedor_fisico_id = cf.id
    GROUP BY tipo_documento
    ORDER BY COUNT(*) DESC
    LIMIT 1
);

-- Reasignar documentos a nuevos contenedores
UPDATE registro_diario rd
SET contenedor_fisico_id = (
    SELECT cf_nuevo.id
    FROM contenedores_fisicos cf_nuevo
    INNER JOIN contenedores_fisicos cf_viejo ON 
        cf_nuevo.tipo_contenedor = cf_viejo.tipo_contenedor
        AND cf_nuevo.numero LIKE CONCAT(cf_viejo.numero, '-%')
    WHERE cf_viejo.id = rd.contenedor_fisico_id
        AND cf_nuevo.numero LIKE CONCAT(
            '%',
            CASE rd.tipo_documento
                WHEN 'REGISTRO_DIARIO' THEN 'DIARIO'
                WHEN 'REGISTRO_INGRESO' THEN 'INGRESO'
                WHEN 'REGISTRO_CEPS' THEN 'CEPS'
                WHEN 'PREVENTIVOS' THEN 'PREV'
                WHEN 'ASIENTOS_MANUALES' THEN 'ASIENTO'
                WHEN 'DIARIOS_APERTURA' THEN 'APERTURA'
                WHEN 'REGISTRO_TRASPASO' THEN 'TRASPASO'
                WHEN 'HOJA_RUTA_DIARIOS' THEN 'HR'
                ELSE SUBSTRING(rd.tipo_documento, 1, 3)
            END
        )
    LIMIT 1
)
WHERE contenedor_fisico_id IN (
    SELECT contenedor_fisico_id
    FROM (
        SELECT contenedor_fisico_id, COUNT(DISTINCT tipo_documento) as tipos
        FROM registro_diario
        GROUP BY contenedor_fisico_id
        HAVING tipos > 1
    ) mixtos
)
AND tipo_documento != (
    SELECT tipo_documento
    FROM registro_diario rd2
    WHERE rd2.contenedor_fisico_id = rd.contenedor_fisico_id
    GROUP BY tipo_documento
    ORDER BY COUNT(*) DESC
    LIMIT 1
);
*/

-- PASO 4: VERIFICACIÓN POST-CORRECCIÓN
-- =====================================================

-- 4.1 ¿Quedan contenedores mixtos?
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ PERFECTO: No hay contenedores mixtos'
        ELSE CONCAT('⚠️ ATENCIÓN: Aún hay ', COUNT(*), ' contenedores mixtos')
    END as resultado
FROM (
    SELECT contenedor_fisico_id
    FROM registro_diario
    GROUP BY contenedor_fisico_id
    HAVING COUNT(DISTINCT tipo_documento) > 1
) mixtos;

-- 4.2 Distribución final de contenedores por tipo
SELECT 
    cf.tipo_contenedor,
    rd.tipo_documento,
    COUNT(DISTINCT cf.id) as contenedores,
    COUNT(rd.id) as documentos
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
GROUP BY cf.tipo_contenedor, rd.tipo_documento
ORDER BY cf.tipo_contenedor, rd.tipo_documento;

-- 4.3 Nuevos contenedores creados
SELECT 
    'NUEVOS CONTENEDORES CREADOS' as info,
    tipo_contenedor,
    numero,
    (SELECT COUNT(*) FROM registro_diario WHERE contenedor_fisico_id = cf.id) as documentos
FROM contenedores_fisicos cf
WHERE numero LIKE '%-%'  -- Contenedores con guión (los nuevos)
ORDER BY tipo_contenedor, numero;
