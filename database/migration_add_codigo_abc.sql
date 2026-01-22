-- Add codigo_abc column to contenedores_fisicos if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'contenedores_fisicos';
SET @columnname = 'codigo_abc';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' VARCHAR(50) DEFAULT NULL AFTER numero_original;')
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SELECT 'Migration completed: codigo_abc added if not exists' as result;
