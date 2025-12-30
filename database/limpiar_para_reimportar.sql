-- =====================================================
-- LIMPIEZA PARA REIMPORTACIÓN COMPLETA
-- Base de Datos: Clever Cloud MySQL
-- ADVERTENCIA: Borra todos los datos actuales
-- =====================================================

SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- LIMPIAR TABLAS EN ORDEN (respetando FK)
-- =====================================================

-- 1. Préstamos (tiene FK a registro_diario)
TRUNCATE TABLE prestamos;
SELECT 'Préstamos eliminados' as paso;

-- 2. Clasificación (si existe)
DELETE FROM clasificacion_contenedor_documento WHERE id > 0;
SELECT 'Clasificaciones eliminadas' as paso;

-- 3. Registros
TRUNCATE TABLE registro_diario;
SELECT 'Registros diarios eliminados' as paso;

TRUNCATE TABLE registro_hojas_ruta;
SELECT 'Hojas de ruta eliminadas' as paso;

-- 4. Contenedores físicos
DELETE FROM contenedores_fisicos WHERE id > 0;
SELECT 'Contenedores físicos eliminados' as paso;

-- =====================================================
-- RESETEAR AUTO_INCREMENT
-- =====================================================
ALTER TABLE contenedores_fisicos AUTO_INCREMENT = 1;
ALTER TABLE registro_diario AUTO_INCREMENT = 1;
ALTER TABLE registro_hojas_ruta AUTO_INCREMENT = 1;
ALTER TABLE clasificacion_contenedor_documento AUTO_INCREMENT = 1;
ALTER TABLE prestamos AUTO_INCREMENT = 1;

-- =====================================================
-- VERIFICACIÓN POST-LIMPIEZA
-- =====================================================
SELECT 
    'VERIFICACIÓN DE LIMPIEZA' as titulo,
    (SELECT COUNT(*) FROM contenedores_fisicos) as contenedores,
    (SELECT COUNT(*) FROM registro_diario) as diarios,
    (SELECT COUNT(*) FROM registro_hojas_ruta) as hojas_ruta,
    (SELECT COUNT(*) FROM clasificacion_contenedor_documento) as clasificaciones,
    (SELECT COUNT(*) FROM prestamos) as prestamos;

-- Todos los valores deben ser 0

SELECT '✅ BASE DE DATOS LIMPIA Y LISTA PARA REIMPORTACIÓN' as resultado;

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
