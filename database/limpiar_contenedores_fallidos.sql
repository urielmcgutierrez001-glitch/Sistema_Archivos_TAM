-- =====================================================
-- LIMPIAR IMPORTACIÓN FALLIDA Y PREPARAR RE-IMPORTACIÓN
-- =====================================================

SET SQL_SAFE_UPDATES = 0;

-- 1. PRIMERO: Limpiar referencias en documentos
UPDATE registro_diario SET contenedor_fisico_id = NULL;
UPDATE registro_hojas_ruta SET contenedor_fisico_id = NULL;

SELECT '✅ Referencias limpiadas' as paso;

-- 2. Limpiar clasificaciones
DELETE FROM clasificacion_contenedor_documento;
ALTER TABLE clasificacion_contenedor_documento AUTO_INCREMENT = 1;

SELECT '✅ Clasificaciones eliminadas' as paso;

-- 3. AHORA SÍ: Limpiar contenedores (ya no hay FKs apuntando)
DELETE FROM contenedores_fisicos;
ALTER TABLE contenedores_fisicos AUTO_INCREMENT = 1;

SELECT '✅ Contenedores eliminados' as paso;

-- 4. Verificar limpieza
SELECT 
    'Verificación' as info,
    (SELECT COUNT(*) FROM contenedores_fisicos) as contenedores,
    (SELECT COUNT(*) FROM clasificacion_contenedor_documento) as clasificaciones,
    (SELECT COUNT(*) FROM registro_diario WHERE contenedor_fisico_id IS NOT NULL) as docs_con_contenedor;

-- Todos deben ser 0

SELECT '✅ Listo para re-importar con script corregido' as resultado;

SET SQL_SAFE_UPDATES = 1;
