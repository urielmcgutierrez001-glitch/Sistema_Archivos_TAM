-- Script para agregar la columna estado_anterior_id a la tabla prestamos
-- y establecer la llave foránea con la tabla estados.

USE tamep_archivos; -- O el nombre de tu base de datos en la nube si es diferente (generalmente se selecciona al conectar)

SET @dbname = DATABASE();
SET @tablename = "prestamos";
SET @columnname = "estado_anterior_id";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " INT NULL AFTER observacion_devolucion;")
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ahora agregamos la FK si no existe (esto puede fallar si ya existe, pero es seguro intentarlo o verificar antes)
-- Para simplificar, intentamos agregarla. Si ya existe el índice, podría dar error, pero la columna ya estará.
-- Verificamos si existe la constraint para no dar error.

SET @constraintName = "fk_prestamos_estado_anterior";
SET @preparedStatementFK = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (constraint_name = @constraintName)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @constraintName, " FOREIGN KEY (", @columnname, ") REFERENCES estados(id);")
));

PREPARE alterFK FROM @preparedStatementFK;
EXECUTE alterFK;
DEALLOCATE PREPARE alterFK;

SELECT "Migración completada exitosamente." as Mensaje;
