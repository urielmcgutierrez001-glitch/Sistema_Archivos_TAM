-- =====================================================
-- DETECCIÓN Y CORRECCIÓN DE CONTENEDORES MIXTOS
-- =====================================================
-- Los contenedores NO deben tener documentos de diferentes tipos
-- Este script identifica y corrige este problema
-- =====================================================

-- PASO 1: IDENTIFICAR CONTENEDORES MIXTOS
-- =====================================================
SELECT 
    'CONTENEDORES MIXTOS DETECTADOS' as alerta,
    cf.id as contenedor_id,
    cf.tipo_contenedor,
    cf.numero,
    GROUP_CONCAT(DISTINCT rd.tipo_documento ORDER BY rd.tipo_documento) as tipos_mezclados,
    COUNT(DISTINCT rd.tipo_documento) as cantidad_tipos_diferentes,
    COUNT(rd.id) as total_documentos
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
GROUP BY cf.id, cf.tipo_contenedor, cf.numero
HAVING cantidad_tipos_diferentes > 1
ORDER BY cantidad_tipos_diferentes DESC, total_documentos DESC;

-- PASO 2: VER DETALLE DE DOCUMENTOS EN CONTENEDORES MIXTOS
-- =====================================================
SELECT 
    'DETALLE DE DOCUMENTOS EN CONTENEDORES MIXTOS' as info,
    cf.id as contenedor_id,
    cf.tipo_contenedor,
    cf.numero as numero_contenedor,
    rd.id as documento_id,
    rd.tipo_documento,
    rd.gestion,
    rd.nro_comprobante,
    rd.codigo_abc
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.id IN (
    -- Subquery para obtener solo contenedores mixtos
    SELECT cf2.id
    FROM contenedores_fisicos cf2
    INNER JOIN registro_diario rd2 ON cf2.id = rd2.contenedor_fisico_id
    GROUP BY cf2.id
    HAVING COUNT(DISTINCT rd2.tipo_documento) > 1
)
ORDER BY cf.id, rd.tipo_documento, rd.gestion;

-- PASO 3: ESTRATEGIA DE CORRECCIÓN
-- =====================================================
-- Opción A: Asignar cada documento al tipo de contenedor predominante
-- Opción B: Crear nuevos contenedores para los tipos minoritarios
-- Opción C: Separar completamente por año y tipo

-- Ver distribución por tipo en cada contenedor mixto
SELECT 
    'DISTRIBUCIÓN POR TIPO EN CONTENEDORES MIXTOS' as info,
    cf.id as contenedor_id,
    cf.numero,
    rd.tipo_documento,
    COUNT(*) as cantidad_docs,
    MIN(rd.gestion) as gestion_min,
    MAX(rd.gestion) as gestion_max
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.id IN (
    SELECT cf2.id
    FROM contenedores_fisicos cf2
    INNER JOIN registro_diario rd2 ON cf2.id = rd2.contenedor_fisico_id
    GROUP BY cf2.id
    HAVING COUNT(DISTINCT rd2.tipo_documento) > 1
)
GROUP BY cf.id, cf.numero, rd.tipo_documento
ORDER BY cf.id, cantidad_docs DESC;

-- =====================================================
-- CORRECCIÓN AUTOMÁTICA - OPCIÓN A
-- =====================================================
-- Mantener el tipo mayoritario, mover los otros a contenedores nuevos

/*
-- PASO 1: Crear contenedores para los tipos minoritarios
-- Este script crea automáticamente nuevos amarros/libros para documentos desplazados

INSERT INTO contenedores_fisicos (tipo_contenedor, numero, ubicacion_id, activo)
SELECT DISTINCT
    cf.tipo_contenedor,
    CONCAT(cf.numero, '-', SUBSTRING(rd.tipo_documento, 1, 3)) as nuevo_numero,
    cf.ubicacion_id,
    1
FROM contenedores_fisicos cf
INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.id IN (
    SELECT cf2.id
    FROM contenedores_fisicos cf2
    INNER JOIN registro_diario rd2 ON cf2.id = rd2.contenedor_fisico_id
    GROUP BY cf2.id
    HAVING COUNT(DISTINCT rd2.tipo_documento) > 1
)
AND rd.tipo_documento NOT IN (
    -- Excluir el tipo predominante
    SELECT rd3.tipo_documento
    FROM registro_diario rd3
    WHERE rd3.contenedor_fisico_id = cf.id
    GROUP BY rd3.tipo_documento
    ORDER BY COUNT(*) DESC
    LIMIT 1
);

-- PASO 2: Reasignar documentos a los nuevos contenedores
UPDATE registro_diario rd
INNER JOIN contenedores_fisicos cf_viejo ON rd.contenedor_fisico_id = cf_viejo.id
INNER JOIN contenedores_fisicos cf_nuevo ON 
    cf_nuevo.numero = CONCAT(cf_viejo.numero, '-', SUBSTRING(rd.tipo_documento, 1, 3))
    AND cf_nuevo.tipo_contenedor = cf_viejo.tipo_contenedor
SET rd.contenedor_fisico_id = cf_nuevo.id
WHERE cf_viejo.id IN (
    SELECT cf2.id
    FROM (
        SELECT cf3.id
        FROM contenedores_fisicos cf3
        INNER JOIN registro_diario rd2 ON cf3.id = rd2.contenedor_fisico_id
        GROUP BY cf3.id
        HAVING COUNT(DISTINCT rd2.tipo_documento) > 1
    ) cf2
)
AND rd.tipo_documento NOT IN (
    SELECT rd3.tipo_documento
    FROM registro_diario rd3
    WHERE rd3.contenedor_fisico_id = cf_viejo.id
    GROUP BY rd3.tipo_documento
    ORDER BY COUNT(*) DESC
    LIMIT 1
);
*/

-- =====================================================
-- CORRECCIÓN MANUAL - TABLA TEMPORAL
-- =====================================================
-- Usa esta opción si prefieres control total

/*
-- 1. Crear tabla temporal para decidir qué hacer con cada documento
CREATE TEMPORARY TABLE temp_reasignacion (
    documento_id INT,
    contenedor_actual_id INT,
    contenedor_nuevo_id INT,
    razon VARCHAR(200)
);

-- 2. Inserta las reasignaciones manualmente
-- Ejemplo:
INSERT INTO temp_reasignacion VALUES
(123, 45, 46, 'Mover REGISTRO_INGRESO a contenedor dedicado'),
(124, 45, 46, 'Mover REGISTRO_INGRESO a contenedor dedicado');

-- 3. Aplicar las reasignaciones
UPDATE registro_diario rd
INNER JOIN temp_reasignacion tr ON rd.id = tr.documento_id
SET rd.contenedor_fisico_id = tr.contenedor_nuevo_id;

-- 4. Verificar
SELECT * FROM temp_reasignacion;

-- 5. Limpiar
DROP TEMPORARY TABLE temp_reasignacion;
*/

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================
-- Ejecuta esto después de la corrección para verificar

SELECT 
    'VERIFICACIÓN: ¿Aún hay contenedores mixtos?' as pregunta,
    COUNT(*) as contenedores_mixtos_restantes
FROM (
    SELECT cf.id
    FROM contenedores_fisicos cf
    INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
    GROUP BY cf.id
    HAVING COUNT(DISTINCT rd.tipo_documento) > 1
) AS mixtos;

-- Si el resultado es 0, ¡todo está bien!
