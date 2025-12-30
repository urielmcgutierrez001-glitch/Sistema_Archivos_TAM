-- =====================================================
-- RECLASIFICACIÓN DE AMARROS Y LIBROS POR TIPO DE DOCUMENTO
-- =====================================================
-- Este script organiza los contenedores físicos de forma más lógica
-- agrupando AMARROS específicamente por tipo de documento
-- =====================================================

-- IMPORTANTE: Ejecuta primero diagnostico_contenedores.sql para ver el estado actual

-- =====================================================
-- OPCIÓN 1: RENUMERAR AMARROS POR TIPO DE DOCUMENTO
-- =====================================================
-- Esta opción crea una numeración secuencial para cada tipo de documento
-- Formato sugerido: TIPO-###
-- Ejemplo: DIARIO-001, DIARIO-002, INGRESO-001, INGRESO-002, etc.

-- Primero, vamos a ver cómo quedaría la nueva numeración (SIN APLICAR CAMBIOS)
SELECT 
    cf.id as contenedor_id,
    cf.tipo_contenedor,
    cf.numero as numero_actual,
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
            WHEN 'HOJA_RUTA_DIARIOS' THEN 'HOJA_RUTA'
            ELSE 'OTRO'
        END,
        '-',
        LPAD(
            ROW_NUMBER() OVER (
                PARTITION BY rd.tipo_documento, cf.tipo_contenedor 
                ORDER BY CAST(cf.numero AS UNSIGNED)
            ),
            3,
            '0'
        )
    ) as nuevo_numero_sugerido,
    COUNT(rd.id) as docs_en_contenedor
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.tipo_contenedor = 'AMARRO' AND rd.tipo_documento IS NOT NULL
GROUP BY cf.id, cf.tipo_contenedor, cf.numero, rd.tipo_documento
ORDER BY rd.tipo_documento, CAST(cf.numero AS UNSIGNED);

-- =====================================================
-- OPCIÓN 2: AGREGAR PREFIJO AL NÚMERO EXISTENTE
-- =====================================================
-- Esta opción mantiene los números actuales pero agrega un prefijo
-- Formato: TIPO_NUMEROACTUAL
-- Ejemplo: Si el amarro es 45 y contiene REGISTRO_DIARIO, quedaría: DIARIO_45

SELECT 
    cf.id as contenedor_id,
    cf.tipo_contenedor,
    cf.numero as numero_actual,
    rd.tipo_documento,
    CONCAT(
        CASE rd.tipo_documento
            WHEN 'REGISTRO_DIARIO' THEN 'DIARIO_'
            WHEN 'REGISTRO_INGRESO' THEN 'INGRESO_'
            WHEN 'REGISTRO_CEPS' THEN 'CEPS_'
            WHEN 'PREVENTIVOS' THEN 'PREV_'
            WHEN 'ASIENTOS_MANUALES' THEN 'ASIENTO_'
            WHEN 'DIARIOS_APERTURA' THEN 'APERTURA_'
            WHEN 'REGISTRO_TRASPASO' THEN 'TRASPASO_'
            WHEN 'HOJA_RUTA_DIARIOS' THEN 'HR_'
            ELSE 'MIXTO_'
        END,
        cf.numero
    ) as nuevo_numero_sugerido
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.tipo_contenedor = 'AMARRO' AND rd.tipo_documento IS NOT NULL
GROUP BY cf.id, cf.tipo_contenedor, cf.numero, rd.tipo_documento
ORDER BY rd.tipo_documento, CAST(cf.numero AS UNSIGNED);

-- =====================================================
-- SCRIPT DE APLICACIÓN - OPCIÓN 1 (RENUMERACIÓN COMPLETA)
-- =====================================================
-- ADVERTENCIA: Este script CAMBIARÁ los números de los amarros
-- Asegúrate de hacer un respaldo de la tabla antes de ejecutar

/*
-- Descomenta para aplicar OPCIÓN 1:

SET @row_num = 0;
SET @current_type = '';

UPDATE contenedores_fisicos cf
INNER JOIN (
    SELECT 
        cf.id,
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
                ELSE 'OTRO'
            END,
            '-',
            LPAD(
                ROW_NUMBER() OVER (
                    PARTITION BY rd.tipo_documento, cf.tipo_contenedor 
                    ORDER BY CAST(cf.numero AS UNSIGNED)
                ),
                3,
                '0'
            )
        ) as nuevo_numero
    FROM contenedores_fisicos cf
    INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
    WHERE cf.tipo_contenedor = 'AMARRO'
    GROUP BY cf.id
) AS nuevos_numeros ON cf.id = nuevos_numeros.id
SET cf.numero = nuevos_numeros.nuevo_numero;
*/

-- =====================================================
-- SCRIPT DE APLICACIÓN - OPCIÓN 2 (AGREGAR PREFIJO)
-- =====================================================
/*
-- Descomenta para aplicar OPCIÓN 2:

UPDATE contenedores_fisicos cf
INNER JOIN (
    SELECT 
        cf.id,
        CONCAT(
            CASE rd.tipo_documento
                WHEN 'REGISTRO_DIARIO' THEN 'DIARIO_'
                WHEN 'REGISTRO_INGRESO' THEN 'INGRESO_'
                WHEN 'REGISTRO_CEPS' THEN 'CEPS_'
                WHEN 'PREVENTIVOS' THEN 'PREV_'
                WHEN 'ASIENTOS_MANUALES' THEN 'ASIENTO_'
                WHEN 'DIARIOS_APERTURA' THEN 'APERTURA_'
                WHEN 'REGISTRO_TRASPASO' THEN 'TRASPASO_'
                WHEN 'HOJA_RUTA_DIARIOS' THEN 'HR_'
                ELSE 'MIXTO_'
            END,
            cf.numero
        ) as nuevo_numero
    FROM contenedores_fisicos cf
    INNER JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
    WHERE cf.tipo_contenedor = 'AMARRO'
    GROUP BY cf.id
) AS nuevos_numeros ON cf.id = nuevos_numeros.id
SET cf.numero = nuevos_numeros.nuevo_numero;
*/

-- =====================================================
-- VERIFICACIÓN POST-APLICACIÓN
-- =====================================================
-- Ejecuta esto después de aplicar los cambios para verificar

SELECT 
    'Verificación: Amarros por Tipo' as categoria,
    cf.tipo_contenedor,
    rd.tipo_documento,
    COUNT(*) as total,
    MIN(cf.numero) as primer_amarro,
    MAX(cf.numero) as ultimo_amarro
FROM contenedores_fisicos cf
LEFT JOIN registro_diario rd ON cf.id = rd.contenedor_fisico_id
WHERE cf.tipo_contenedor = 'AMARRO'
GROUP BY cf.tipo_contenedor, rd.tipo_documento
ORDER BY rd.tipo_documento;
