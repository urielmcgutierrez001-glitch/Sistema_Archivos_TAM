-- Force migration
DROP PROCEDURE IF EXISTS upgrade_prestamos;

DELIMITER //

CREATE PROCEDURE upgrade_prestamos()
BEGIN
    -- Check if unidad_area_id exists
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'prestamos_encabezados' 
        AND COLUMN_NAME = 'unidad_area_id'
    ) THEN
        ALTER TABLE prestamos_encabezados
        ADD COLUMN unidad_area_id INT NULL AFTER usuario_id;
    END IF;

    -- Check if nombre_prestatario exists
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'prestamos_encabezados' 
        AND COLUMN_NAME = 'nombre_prestatario'
    ) THEN
        ALTER TABLE prestamos_encabezados
        ADD COLUMN nombre_prestatario VARCHAR(255) NULL AFTER unidad_area_id;
    END IF;
    
    -- Add FK if not exists (Checking constraint is harder, so we try-catch basically by ignoring error on ALTER or just running it)
    -- Simplified: Just run it, if it fails it fails (likely exists)
END//

DELIMITER ;

CALL upgrade_prestamos();

-- Execute FK addition separately (if it fails, it means it exists or column issue, but valid column check above helps)
-- We wrap in a block just in case
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_prestamo_unidad' AND TABLE_NAME = 'prestamos_encabezados');
SET @sql = IF(@fk_exists = 0, 'ALTER TABLE prestamos_encabezados ADD CONSTRAINT fk_prestamo_unidad FOREIGN KEY (unidad_area_id) REFERENCES ubicaciones(id)', 'SELECT "FK already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DROP PROCEDURE upgrade_prestamos;
