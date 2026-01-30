-- =============================================================================
-- SCRIPT DE VERIFICACIÓN DE CONSISTENCIA DE DATOS (NUBE)
-- =============================================================================
-- Instrucciones:
-- 1. Conéctese a su base de datos en la nube (Clever Cloud, AWS, etc.).
-- 2. Asegúrese de estar usando la base de datos correcta.
--    Ejemplo: USE nombre_de_su_base_de_datos;
-- 3. Ejecute las siguientes consultas.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. VERIFICAR COINCIDENCIA DE GESTIÓN (AÑO)
-- Comprueba si hay documentos cuya 'gestión' (año) no coincida con la de su contenedor.
-- Resultado esperado: 0 filas.
-- -----------------------------------------------------------------------------
SELECT 
    d.id AS documento_id,
    d.nro_comprobante,
    COALESCE(td.codigo, 'N/A') AS tipo_documento,
    d.gestion AS doc_gestion,
    c.numero AS cont_numero,
    c.gestion AS cont_gestion
FROM documentos d
JOIN contenedores_fisicos c ON d.contenedor_fisico_id = c.id
LEFT JOIN tipo_documento td ON d.tipo_documento_id = td.id
WHERE d.gestion != c.gestion
ORDER BY c.numero, d.nro_comprobante;


-- -----------------------------------------------------------------------------
-- 2. VERIFICAR HOMOGENEIDAD DE TIPOS DE DOCUMENTO POR CONTENEDOR
-- Comprueba si existen contenedores que almacenen más de un tipo de documento diferente.
-- Resultado esperado: 0 filas (si la regla es 1 tipo por contenedor).
-- -----------------------------------------------------------------------------
SELECT 
    c.id AS contenedor_id,
    c.numero AS cont_numero,
    c.gestion AS cont_gestion,
    COUNT(DISTINCT d.tipo_documento_id) AS cantidad_tipos_distintos,
    GROUP_CONCAT(DISTINCT COALESCE(td.codigo, 'Unknown')) AS tipos_encontrados
FROM contenedores_fisicos c
JOIN documentos d ON c.id = d.contenedor_fisico_id
LEFT JOIN tipo_documento td ON d.tipo_documento_id = td.id
GROUP BY c.id
HAVING cantidad_tipos_distintos > 1
ORDER BY c.numero;
