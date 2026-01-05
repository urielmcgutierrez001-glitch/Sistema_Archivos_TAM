-- Create table for loan headers (groups)
CREATE TABLE IF NOT EXISTS prestamos_encabezados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_devolucion_esperada DATE NOT NULL,
    observaciones TEXT,
    estado VARCHAR(20) DEFAULT 'Prestado', -- 'Prestado', 'Devuelto', 'Parcial'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key to prestamos table to link to header
-- We check if column exists first (simplified for this raw script context)
-- Since we can't do conditional ADD COLUMN easily in one statement without stored procs, 
-- we will just run the ALTER and ignore "Duplicate column" errors or check via PHP.
-- For this script, we'll try to add it.

ALTER TABLE prestamos ADD COLUMN encabezado_id INT NULL AFTER id;
ALTER TABLE prestamos ADD CONSTRAINT fk_prestamo_encabezado FOREIGN KEY (encabezado_id) REFERENCES prestamos_encabezados(id);
