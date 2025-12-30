-- =====================================================
-- RECUPERACIÓN DE NÚMEROS ORIGINALES DEL EXCEL
-- =====================================================
-- Este script ayuda a identificar contenedores que perdieron su numeración original
-- =====================================================

-- 1. Ver todos los contenedores con su numeración actual
-- Esto te ayudará a compararlos con el Excel
SELECT 
    id,
    tipo_contenedor,
    numero,
    bloque_nivel,
    color,
    descripcion,
    ubicacion_id,
    fecha_creacion
FROM contenedores_fisicos
ORDER BY 
    tipo_contenedor,
    CASE 
        WHEN numero REGEXP '^[0-9]+$' THEN CAST(numero AS UNSIGNED)
        ELSE 9999999
    END,
    numero;

-- 2. Exportar lista para comparar con Excel
-- Copia el resultado y compáralo con tu Excel original
SELECT 
    CONCAT(tipo_contenedor, ' #', numero) as contenedor,
    bloque_nivel,
    color,
    (SELECT COUNT(*) FROM registro_diario WHERE contenedor_fisico_id = cf.id) as docs_asignados
FROM contenedores_fisicos cf
ORDER BY tipo_contenedor, numero;

-- 3. Ver amarros sospechosos (números muy altos o formato extraño)
SELECT 
    'Amarros Sospechosos' as categoria,
    id,
    numero,
    bloque_nivel,
    CASE 
        WHEN numero REGEXP '^[0-9]+$' THEN 'Solo números'
        WHEN numero REGEXP '[A-Z]' THEN 'Contiene letras'
        ELSE 'Formato extraño'
    END as formato,
    LENGTH(numero) as longitud
FROM contenedores_fisicos
WHERE tipo_contenedor = 'AMARRO'
    AND (
        LENGTH(numero) > 4  -- Números muy largos
        OR numero REGEXP '[^0-9A-Z_-]'  -- Caracteres extraños
    )
ORDER BY CAST(numero AS UNSIGNED) DESC;

-- 4. Ver libros sospechosos
SELECT 
    'Libros Sospechosos' as categoria,
    id,
    numero,
    color,
    bloque_nivel,
    CASE 
        WHEN numero REGEXP '^[0-9]+$' THEN 'Solo números'
        WHEN numero REGEXP '[A-Z]' THEN 'Contiene letras'
        ELSE 'Formato extraño'
    END as formato
FROM contenedores_fisicos
WHERE tipo_contenedor = 'LIBRO'
    AND (
        LENGTH(numero) > 4
        OR numero REGEXP '[^0-9A-Z_-]'
        OR color IS NULL  -- Libros sin color (incorrecto)
    );

-- =====================================================
-- CORRECCIÓN MANUAL DE NÚMEROS
-- =====================================================
-- Usa esta plantilla para corregir números específicos

/*
-- Ejemplo: Corregir un amarro que tiene número incorrecto
UPDATE contenedores_fisicos 
SET numero = '45'  -- Número correcto del Excel
WHERE id = 123;  -- ID del contenedor a corregir

-- Ejemplo: Corregir varios amarros a la vez
UPDATE contenedores_fisicos 
SET numero = CASE id
    WHEN 123 THEN '45'
    WHEN 124 THEN '46'
    WHEN 125 THEN '47'
    -- Agrega más casos aquí
END
WHERE id IN (123, 124, 125);  -- IDs a corregir
*/

-- =====================================================
-- TEMPLATE PARA CORREGIR EN LOTE DESDE EXCEL
-- =====================================================
-- Si tienes muchos para corregir, crea un archivo temporal con la data correcta

/*
-- 1. Primero crea una tabla temporal con los datos del Excel:
CREATE TEMPORARY TABLE temp_numeros_correctos (
    contenedor_id INT,
    numero_correcto VARCHAR(50)
);

-- 2. Inserta los datos correctos (puedes generarlo desde Excel)
INSERT INTO temp_numeros_correctos (contenedor_id, numero_correcto) VALUES
(123, '45'),
(124, '46'),
(125, '47');
-- ...continúa con más filas

-- 3. Aplica las correcciones
UPDATE contenedores_fisicos cf
INNER JOIN temp_numeros_correctos tnc ON cf.id = tnc.contenedor_id
SET cf.numero = tnc.numero_correcto;

-- 4. Verifica los cambios
SELECT 
    cf.id,
    cf.tipo_contenedor,
    cf.numero as numero_actualizado,
    tnc.numero_correcto
FROM contenedores_fisicos cf
INNER JOIN temp_numeros_correctos tnc ON cf.id = tnc.contenedor_id;

-- 5. Elimina la tabla temporal
DROP TEMPORARY TABLE temp_numeros_correctos;
*/

-- =====================================================
-- BÚSQUEDA DE PATTERN DE NUMERACIÓN
-- =====================================================
-- Ayuda a identificar el patrón de numeración que se usó

SELECT 
    tipo_contenedor,
    COUNT(*) as total,
    MIN(CAST(numero AS UNSIGNED)) as min_numero,
    MAX(CAST(numero AS UNSIGNED)) as max_numero,
    COUNT(DISTINCT numero) as numeros_unicos,
    (MAX(CAST(numero AS UNSIGNED)) - MIN(CAST(numero AS UNSIGNED)) + 1) as rango_esperado
FROM contenedores_fisicos
WHERE numero REGEXP '^[0-9]+$'  -- Solo números puros
GROUP BY tipo_contenedor;
