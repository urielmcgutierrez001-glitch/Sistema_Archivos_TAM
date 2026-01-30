-- Script DEFINITIVO y ATÓMICO
-- Intentamos alinear todo para que no haya excusas de "charset" o "engine"

-- 1. Asegurarnos que la tabla estados usa InnoDB (fundamental para FK)
ALTER TABLE estados ENGINE = InnoDB;

-- 2. Asegurarnos que la tabla prestamos usa InnoDB
ALTER TABLE prestamos ENGINE = InnoDB;

-- 3. Agregar la columna asegurando que sea INT(11) o simplemente INT, y UNSIGNED si el ID lo fuera (en la foto no dice unsigned, así que INT normal)
-- Usamos add column IF NOT EXISTS workaround o simplemente intentamos agregarla.
-- Si ya existe, esta línea dará error, pero puedes comentarla si ya la agregaste manualmente.
ALTER TABLE prestamos ADD COLUMN estado_anterior_id INT NULL;

-- 4. Crear el índice explícitamente primero (ayuda a depurar)
CREATE INDEX idx_prestamos_estado_anterior ON prestamos(estado_anterior_id);

-- 5. Agregar la FK con especificación simple
ALTER TABLE prestamos 
ADD CONSTRAINT fk_prestamos_estado_anterior 
FOREIGN KEY (estado_anterior_id) REFERENCES estados(id);
