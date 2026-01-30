-- Script COMPLETO para migrar 'estados' y relacionar 'prestamos'
-- Este script crea la tabla si no existe y luego agrega la columna a prestamos.

-- 1. Crear tabla 'estados' (si no existe)
CREATE TABLE IF NOT EXISTS estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insertar estados base (ignora si ya existen por el nombre UNIQUE)
INSERT IGNORE INTO estados (nombre, descripcion) VALUES 
('DISPONIBLE', 'Documento disponible en archivo'),
('PRESTADO', 'Documento prestado a un usuario'),
('FALTA', 'Documento no encontrado en su ubicación'),
('ELIMINADO', 'Documento dado de baja logicamente');

-- 3. Agregar la columna 'estado_anterior_id' a 'prestamos' (si no existe)
-- Usamos un bloque seguro con procedimiento almacenado temporal para evitar errores
DROP PROCEDURE IF EXISTS AddColumnIfMissing;
DELIMITER //
CREATE PROCEDURE AddColumnIfMissing()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'prestamos' AND COLUMN_NAME = 'estado_anterior_id'
    ) THEN
        ALTER TABLE prestamos ADD COLUMN estado_anterior_id INT NULL;
    END IF;
END //
DELIMITER ;
CALL AddColumnIfMissing();
DROP PROCEDURE AddColumnIfMissing;

-- 4. Agregar la Foreign Key (si no existe)
DROP PROCEDURE IF EXISTS AddFKIfMissing;
DELIMITER //
CREATE PROCEDURE AddFKIfMissing()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_NAME = 'prestamos' AND CONSTRAINT_NAME = 'fk_prestamos_estado_anterior'
    ) THEN
        ALTER TABLE prestamos ADD CONSTRAINT fk_prestamos_estado_anterior 
        FOREIGN KEY (estado_anterior_id) REFERENCES estados(id);
    END IF;
END //
DELIMITER ;
CALL AddFKIfMissing();
DROP PROCEDURE AddFKIfMissing;

SELECT "Migración completa: Tabla 'estados' creada y vinculada a 'prestamos'." as Mensaje;
