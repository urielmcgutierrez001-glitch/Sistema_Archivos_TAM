-- Script FINAL y SEGURO para agregar la columna
-- 1. Agregamos la columna al final de la tabla (sin usar AFTER, así no falla si falta otra columna)
ALTER TABLE prestamos ADD COLUMN estado_anterior_id INT NULL;

-- 2. Agregamos la llave foránea
ALTER TABLE prestamos ADD CONSTRAINT fk_prestamos_estado_anterior 
FOREIGN KEY (estado_anterior_id) REFERENCES estados(id);
