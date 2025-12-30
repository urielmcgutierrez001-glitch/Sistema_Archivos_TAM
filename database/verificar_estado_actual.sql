-- Verificar estructura actual de registro_diario
DESC registro_diario;

-- Ver valores únicos de estado si existe
SELECT DISTINCT estado_documento, COUNT(*) as total
FROM registro_diario
WHERE estado_documento IS NOT NULL
GROUP BY estado_documento;

-- Ver si hay campo de estado en la tabla
SHOW COLUMNS FROM registro_diario LIKE '%estado%';

-- Verificar si existe tabla de estados
SHOW TABLES LIKE '%estado%';
