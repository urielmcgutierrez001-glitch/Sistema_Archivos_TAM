-- Migration: Add unit/area and borrower name to loan headers

-- 1. Add columns to prestamos_encabezados
ALTER TABLE prestamos_encabezados
ADD COLUMN unidad_area_id INT NULL AFTER usuario_id,
ADD COLUMN nombre_prestatario VARCHAR(255) NULL AFTER unidad_area_id;

-- 2. Add Foreign Key for unidad_area_id (linking to ubicaciones)
ALTER TABLE prestamos_encabezados
ADD CONSTRAINT fk_prestamo_unidad
FOREIGN KEY (unidad_area_id) REFERENCES ubicaciones(id);

-- 3. Ensure ubicacion_id exists in contenedores_fisicos (just to be safe, though scripts have it)
-- This part is conditional in case it was already added by python scripts, 
-- but SQL doesn't support IF NOT EXISTS for columns easily in one statement without stored procs.
-- We assume the python scripts ran or we handle it gracefully if it fails (it might error if exists).
-- Better approach: Check if column exists or just run it and ignore "Duplicate column" error if manually running.
-- Since I'm using PHP to run this, I'll stick to the safe alterations above.

-- 4. Update existing records (Optional: seed old records with a default if needed, or leave NULL)
-- Setting them to NULL is fine for history, effectively saying "System User" was the borrower before this change.
