-- =====================================================
-- BACKUP BASE DE DATOS TAMEP
-- Fecha: 2025-12-22
-- Propósito: Backup antes de migración registro_egreso
-- =====================================================

-- INSTRUCCIONES:
-- 1. Ejecutar desde MySQL Workbench o línea de comandos:
--    mysqldump -u root -p tamep_archivos > backup_tamep_2025-12-22.sql
--
-- 2. O ejecutar este comando desde PowerShell:
--    cd "C:\Users\PCA\Desktop\Pasantia TAM\Sistema Gestion de Archivos\Proyecto\database"
--    mysqldump -u root -p tamep_archivos > backup_tamep_2025-12-22.sql

-- RESTAURAR (si es necesario):
-- mysql -u root -p tamep_archivos < backup_tamep_2025-12-22.sql
