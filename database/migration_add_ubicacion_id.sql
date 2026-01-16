ALTER TABLE registro_diario ADD COLUMN ubicacion_id INT NULL AFTER contenedor_fisico_id;
ALTER TABLE registro_diario ADD CONSTRAINT fk_registro_diario_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id);
