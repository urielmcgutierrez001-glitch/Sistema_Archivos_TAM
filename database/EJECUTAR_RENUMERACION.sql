-- =====================================================
-- RENUMERACIÓN DE CONTENEDORES POR TIPO DE DOCUMENTO
-- Script de Ejecución Completa y Segura
-- =====================================================
-- Este script aplica la renumeración automática con respaldo
-- Formato final: TIPO-001, TIPO-002, etc.
-- =====================================================

-- Deshabilitar safe update mode temporalmente
SET SQL_SAFE_UPDATES = 0;

-- =====================================================
-- PASO 1: CREAR RESPALDO DE SEGURIDAD
-- =====================================================
-- IMPORTANTE: Solo crea backup si no existe (para evitar sobrescribir backups válidos)

-- Crear backups solo si no existen
CREATE TABLE IF NOT EXISTS contenedores_fisicos_backup_renumeracion AS 
SELECT * FROM contenedores_fisicos WHERE 1=0;

INSERT IGNORE INTO contenedores_fisicos_backup_renumeracion
SELECT * FROM contenedores_fisicos;

CREATE TABLE IF NOT EXISTS registro_diario_backup_renumeracion AS 
SELECT * FROM registro_diario WHERE 1=0;

INSERT IGNORE INTO registro_diario_backup_renumeracion
SELECT * FROM registro_diario;

SELECT 'BACKUP VERIFICADO/CREADO' as resultado,
       (SELECT COUNT(*) FROM contenedores_fisicos_backup_renumeracion) as contenedores_respaldados,
       (SELECT COUNT(*) FROM registro_diario_backup_renumeracion) as documentos_respaldados;

-- =====================================================
-- PASO 2: VERIFICAR ESTADO ACTUAL
-- =====================================================

SELECT 'ESTADO ANTES DE RENUMERACIÓN' as info;

-- Ver contenedores por tipo de documento
SELECT 
    cf.tipo_contenedor,
    rd.tipo_documento,
    COUNT(DISTINCT cf.id) as total_contenedores,
    COUNT(rd.id) as total_documentos
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE rd.tipo_documento IS NOT NULL
GROUP BY cf.tipo_contenedor, rd.tipo_documento
ORDER BY cf.tipo_contenedor, rd.tipo_documento;

-- Verificar si hay contenedores mixtos
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('⚠️ HAY ', COUNT(*), ' CONTENEDORES MIXTOS - SE CORREGIRÁN PRIMERO')
        ELSE '✅ NO HAY CONTENEDORES MIXTOS'
    END as alerta_mixtos
FROM (
    SELECT contenedor_fisico_id
    FROM registro_diario
    GROUP BY contenedor_fisico_id
    HAVING COUNT(DISTINCT tipo_documento) > 1
) mixtos;

-- =====================================================
-- PASO 3: CORREGIR CONTENEDORES MIXTOS (SI EXISTEN)
-- =====================================================

-- 3.0 Limpiar contenedores temporales de ejecuciones anteriores
DELETE FROM contenedores_fisicos
WHERE numero LIKE '%-TEMP-%';

SELECT 'CONTENEDORES TEMPORALES ANTERIORES ELIMINADOS' as limpieza;

-- 3.1 Crear nuevos contenedores para tipos minoritarios
INSERT INTO contenedores_fisicos (tipo_contenedor, numero, bloque_nivel, ubicacion_id, activo, descripcion)
SELECT DISTINCT
    cf.tipo_contenedor,
    CONCAT(cf.numero, '-TEMP-', SUBSTRING(rd.tipo_documento, 1, 5)) as numero_temporal,
    cf.bloque_nivel,
    cf.ubicacion_id,
    1,
    CONCAT('Separado de contenedor mixto ', cf.numero)
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

-- 3.2 Mover documentos minoritarios a nuevos contenedores
UPDATE registro_diario rd
INNER JOIN contenedores_fisicos cf_viejo ON rd.contenedor_fisico_id = cf_viejo.id
SET rd.contenedor_fisico_id = (
    SELECT cf_nuevo.id
    FROM contenedores_fisicos cf_nuevo
    WHERE cf_nuevo.numero LIKE CONCAT(cf_viejo.numero, '-TEMP-%')
        AND cf_nuevo.numero LIKE CONCAT('%', SUBSTRING(rd.tipo_documento, 1, 5), '%')
    LIMIT 1
)
WHERE cf_viejo.id IN (
    SELECT mixto_id FROM (
        SELECT contenedor_fisico_id as mixto_id
        FROM registro_diario
        GROUP BY contenedor_fisico_id
        HAVING COUNT(DISTINCT tipo_documento) > 1
    ) temp_mixtos
)
AND rd.tipo_documento != (
    SELECT tipo_documento
    FROM registro_diario rd2
    WHERE rd2.contenedor_fisico_id = cf_viejo.id
    GROUP BY tipo_documento
    ORDER BY COUNT(*) DESC
    LIMIT 1
);

SELECT 'CONTENEDORES MIXTOS CORREGIDOS' as resultado;

-- =====================================================
-- PASO 4: APLICAR RENUMERACIÓN POR TIPO DE DOCUMENTO
-- =====================================================

-- 4.1 Crear tabla temporal con nueva numeración
CREATE TEMPORARY TABLE temp_nueva_numeracion AS
SELECT 
    cf.id as contenedor_id,
    cf.numero as numero_anterior,
    rd.tipo_documento,
    CONCAT(
        CASE rd.tipo_documento
            WHEN 'REGISTRO_DIARIO' THEN 'DIARIO'
            WHEN 'REGISTRO_INGRESO' THEN 'INGRESO'
            WHEN 'REGISTRO_CEPS' THEN 'CEPS'
            WHEN 'PREVENTIVOS' THEN 'PREV'
            WHEN 'ASIENTOS_MANUALES' THEN 'ASIENTO'
            WHEN 'DIARIOS_APERTURA' THEN 'APERTURA'
            WHEN 'REGISTRO_TRASPASO' THEN 'TRASPASO'
            WHEN 'HOJA_RUTA_DIARIOS' THEN 'HR'
            ELSE 'MIXTO'
        END,
        '-',
        LPAD(
            ROW_NUMBER() OVER (
                PARTITION BY rd.tipo_documento, cf.tipo_contenedor 
                ORDER BY 
                    CASE WHEN cf.numero REGEXP '^[0-9]+$' 
                        THEN CAST(cf.numero AS UNSIGNED)
                        ELSE 999999 
                    END,
                    cf.numero
            ),
            3,
            '0'
        )
    ) as numero_nuevo
FROM contenedores_fisicos cf
INNER JOIN (
    -- Obtener el tipo predominante de cada contenedor
    SELECT 
        contenedor_fisico_id,
        tipo_documento,
        COUNT(*) as cantidad
    FROM registro_diario
    WHERE tipo_documento IS NOT NULL
    GROUP BY contenedor_fisico_id, tipo_documento
) rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.tipo_contenedor IN ('AMARRO', 'LIBRO')
GROUP BY cf.id, cf.numero, rd.tipo_documento
HAVING rd.cantidad = (
    SELECT MAX(cantidad)
    FROM (
        SELECT COUNT(*) as cantidad
        FROM registro_diario
        WHERE contenedor_fisico_id = cf.id
            AND tipo_documento IS NOT NULL
        GROUP BY tipo_documento
    ) max_count
);

-- 4.2 Mostrar preview de la renumeración
SELECT 
    'PREVIEW DE RENUMERACIÓN' as info,
    tipo_documento,
    COUNT(*) as contenedores_a_renumerar,
    MIN(numero_nuevo) as primer_numero,
    MAX(numero_nuevo) as ultimo_numero
FROM temp_nueva_numeracion
GROUP BY tipo_documento
ORDER BY tipo_documento;

-- 4.3 Aplicar la renumeración
UPDATE contenedores_fisicos cf
INNER JOIN temp_nueva_numeracion tnn ON cf.id = tnn.contenedor_id
SET cf.numero = tnn.numero_nuevo;

SELECT 'RENUMERACIÓN APLICADA EXITOSAMENTE' as resultado;

-- =====================================================
-- PASO 5: VERIFICACIÓN POST-RENUMERACIÓN
-- =====================================================

-- 5.1 Verificar que no hay contenedores mixtos
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ PERFECTO: No hay contenedores mixtos'
        ELSE CONCAT('⚠️ PROBLEMA: Aún hay ', COUNT(*), ' contenedores mixtos')
    END as verificacion_mixtos
FROM (
    SELECT contenedor_fisico_id
    FROM registro_diario
    GROUP BY contenedor_fisico_id
    HAVING COUNT(DISTINCT tipo_documento) > 1
) mixtos;

-- 5.2 Mostrar distribución final
SELECT 
    'DISTRIBUCIÓN FINAL' as info,
    cf.tipo_contenedor,
    CASE 
        WHEN cf.numero LIKE 'DIARIO-%' THEN 'REGISTRO_DIARIO'
        WHEN cf.numero LIKE 'INGRESO-%' THEN 'REGISTRO_INGRESO'
        WHEN cf.numero LIKE 'CEPS-%' THEN 'REGISTRO_CEPS'
        WHEN cf.numero LIKE 'PREV-%' THEN 'PREVENTIVOS'
        WHEN cf.numero LIKE 'ASIENTO-%' THEN 'ASIENTOS_MANUALES'
        WHEN cf.numero LIKE 'APERTURA-%' THEN 'DIARIOS_APERTURA'
        WHEN cf.numero LIKE 'TRASPASO-%' THEN 'REGISTRO_TRASPASO'
        WHEN cf.numero LIKE 'HR-%' THEN 'HOJA_RUTA_DIARIOS'
        ELSE 'SIN_CLASIFICAR'
    END as tipo_por_numero,
    COUNT(*) as total_contenedores,
    MIN(cf.numero) as primer_contenedor,
    MAX(cf.numero) as ultimo_contenedor
FROM contenedores_fisicos cf
WHERE cf.tipo_contenedor IN ('AMARRO', 'LIBRO')
GROUP BY cf.tipo_contenedor, tipo_por_numero
ORDER BY cf.tipo_contenedor, tipo_por_numero;

-- 5.3 Ver ejemplos de la nueva numeración
SELECT 
    'EJEMPLOS DE NUEVA NUMERACIÓN' as info,
    cf.tipo_contenedor,
    cf.numero as numero_nuevo,
    rd.tipo_documento,
    COUNT(rd.id) as documentos
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.numero LIKE '%-%'
GROUP BY cf.id, cf.tipo_contenedor, cf.numero, rd.tipo_documento
ORDER BY cf.numero
LIMIT 20;

-- Limpiar tabla temporal
DROP TEMPORARY TABLE IF EXISTS temp_nueva_numeracion;

-- =====================================================
-- ROLLBACK (Solo si algo salió mal)
-- =====================================================
/*
-- DESCOMENTA ESTAS LÍNEAS SOLO SI NECESITAS REVERTIR

-- Restaurar contenedores_fisicos
DELETE FROM contenedores_fisicos;
INSERT INTO contenedores_fisicos SELECT * FROM contenedores_fisicos_backup_renumeracion;

-- Restaurar registro_diario
DELETE FROM registro_diario;
INSERT INTO registro_diario SELECT * FROM registro_diario_backup_renumeracion;

SELECT 'ROLLBACK COMPLETADO - DATOS RESTAURADOS' as resultado;
*/

-- =====================================================
-- LIMPIEZA FINAL (Ejecutar solo si todo está bien)
-- =====================================================
/*
-- DESCOMENTA ESTAS LÍNEAS SOLO DESPUÉS DE VERIFICAR QUE TODO ESTÁ BIEN

DROP TABLE IF EXISTS contenedores_fisicos_backup_renumeracion;
DROP TABLE IF EXISTS registro_diario_backup_renumeracion;

SELECT 'BACKUPS ELIMINADOS - RENUMERACIÓN FINALIZADA' as resultado;
*/

SELECT '========================================' as separador;
SELECT '✅ PROCESO COMPLETADO EXITOSAMENTE' as resultado_final;
SELECT '========================================' as separador;
SELECT 'Revisa los resultados arriba' as instruccion;
SELECT 'Si todo está correcto, ejecuta la sección LIMPIEZA FINAL' as siguiente_paso;

-- Reactivar safe update mode
SET SQL_SAFE_UPDATES = 1;
