-- Eliminación de restricciones únicas para permitir duplicados
-- 
-- IMPORTANTE: Esto permitirá que se inserten múltiples registros
-- con la misma gestión, nro_comprobante y tipo_documento.

-- Desactivar verificación FK por seguridad
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar índice único uk_diario si existe
SET @exist := (SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'documentos' 
    AND index_name = 'uk_diario');

SET @sqlstmt := IF(@exist > 0, 'ALTER TABLE documentos DROP INDEX uk_diario', 'SELECT "Index uk_diario does not exist"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar índice único uk_validacion si existe (por si acaso)
SET @exist2 := (SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'documentos' 
    AND index_name = 'uk_validacion');

SET @sqlstmt2 := IF(@exist2 > 0, 'ALTER TABLE documentos DROP INDEX uk_validacion', 'SELECT "Index uk_validacion does not exist"');
PREPARE stmt2 FROM @sqlstmt2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Reactivar FK
SET FOREIGN_KEY_CHECKS = 1;

SELECT "Restricciones únicas eliminadas exitosamente" as resultado;
