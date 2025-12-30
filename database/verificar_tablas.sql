-- =====================================================
-- VERIFICACIÓN DE TABLAS - Sistema TAMEP
-- Ejecuta estas consultas para ver qué tablas existen
-- =====================================================

-- Ver todas las tablas que empiezan con "registro_"
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'tamep_archivos' 
AND TABLE_NAME LIKE 'registro_%'
ORDER BY TABLE_NAME;

-- Ver estructura de cada tabla (ejecutar una por una)

-- registro_diarios_apertura
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'tamep_archivos' 
AND TABLE_NAME = 'registro_diarios_apertura'
ORDER BY ORDINAL_POSITION;

-- registro_ingreso
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'tamep_archivos' 
AND TABLE_NAME = 'registro_ingreso'
ORDER BY ORDINAL_POSITION;

-- registro_preventivos  
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'tamep_archivos' 
AND TABLE_NAME = 'registro_preventivos'
ORDER BY ORDINAL_POSITION;

-- registro_traspaso
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'tamep_archivos' 
AND TABLE_NAME = 'registro_traspaso'
ORDER BY ORDINAL_POSITION;
