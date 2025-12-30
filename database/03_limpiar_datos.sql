-- =====================================================
-- LIMPIEZA DE DATOS ACTUALES
-- ADVERTENCIA: Este script BORRA todos los datos
-- Ejecutar SOLO después de crear backup
-- =====================================================

SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;

--  Verificar que existe backup
SELECT CASE 
    WHEN (SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'tamep_backup_20251223') = 0
    THEN 'ERROR: NO EXISTE BACKUP - NO EJECUTAR'
    ELSE 'BACKUP ENCONTRADO - PUEDE CONTINUAR'
END as verificacion_backup;

-- =====================================================
-- LIMPIAR TABLA: prestamos (primero por FK)
-- =====================================================
TRUNCATE TABLE prestamos;
SELECT 'Préstamos eliminados' as paso;

-- =====================================================
-- LIMPIAR TABLA: clasificacion_contenedor_documento (si existe)
-- =====================================================
SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'clasificacion_contenedor_documento'
);

SET @sql_truncate_clasificacion = IF(@table_exists > 0,
    'TRUNCATE TABLE clasificacion_contenedor_documento',
    'SELECT "Tabla clasificacion_contenedor_documento no existe aún" as msg');
PREPARE stmt FROM @sql_truncate_clasificacion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Clasificaciones eliminadas (si existían)' as paso;

-- =====================================================
-- LIMPIAR TABLA: registro_diario
-- =====================================================
TRUNCATE TABLE registro_diario;
SELECT 'Registros diarios eliminados' as paso;

-- =====================================================
-- LIMPIAR TABLA: registro_hojas_ruta
-- =====================================================
TRUNCATE TABLE registro_hojas_ruta;
SELECT 'Hojas de ruta eliminadas' as paso;

-- =====================================================
-- LIMPIAR TABLA: contenedores_fisicos
-- =====================================================
DELETE FROM contenedores_fisicos;
SELECT 'Contenedores físicos eliminados' as paso;

-- Resetear auto_increment
ALTER TABLE contenedores_fisicos AUTO_INCREMENT = 1;
ALTER TABLE registro_diario AUTO_INCREMENT = 1;
ALTER TABLE registro_hojas_ruta AUTO_INCREMENT = 1;

-- Reset clasificacion solo si existe
SET @sql_reset_clasificacion = IF(
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clasificacion_contenedor_documento') > 0,
    'ALTER TABLE clasificacion_contenedor_documento AUTO_INCREMENT = 1',
    'SELECT "Tabla clasificacion no existe" as msg'
);
PREPARE stmt FROM @sql_reset_clasificacion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- VERIFICACIÓN POST-LIMPIEZA
-- =====================================================
-- Verificación básica (sin tabla clasificacion que puede no existir aún)
SELECT 
    'VERIFICACIÓN DE LIMPIEZA' as titulo,
    (SELECT COUNT(*) FROM contenedores_fisicos) as contenedores,
    (SELECT COUNT(*) FROM registro_diario) as diarios,
    (SELECT COUNT(*) FROM registro_hojas_ruta) as hojas_ruta,
    (SELECT COUNT(*) FROM prestamos) as prestamos;

-- Todos los valores deben ser 0

SELECT '✅ BASE DE DATOS LIMPIA Y LISTA PARA IMPORTACIÓN' as resultado;

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
