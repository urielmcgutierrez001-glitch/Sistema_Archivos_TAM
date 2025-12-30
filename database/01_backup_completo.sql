-- =====================================================
-- BACKUP COMPLETO DE BASE DE DATOS
-- Ejecutar ANTES de cualquier cambio destructivo
-- =====================================================
-- Fecha: 2025-12-23
-- Propósito: Respaldar base de datos antes de reimportación completa
-- =====================================================

-- Crear base de datos de backup
DROP DATABASE IF EXISTS tamep_backup_20251223;
CREATE DATABASE tamep_backup_20251223;

-- Backup de contenedores_fisicos
CREATE TABLE tamep_backup_20251223.contenedores_fisicos AS
SELECT * FROM contenedores_fisicos;

-- Backup de registro_diario
CREATE TABLE tamep_backup_20251223.registro_diario AS
SELECT * FROM registro_diario;

-- Backup de registro_hojas_ruta (si existe)
CREATE TABLE tamep_backup_20251223.registro_hojas_ruta AS
SELECT * FROM registro_hojas_ruta;

-- Backup de ubicaciones (si existe)
CREATE TABLE tamep_backup_20251223.ubicaciones AS
SELECT * FROM ubicaciones;

-- Backup de prestamos
CREATE TABLE tamep_backup_20251223.prestamos AS
SELECT * FROM prestamos;

-- Backup de usuarios
CREATE TABLE tamep_backup_20251223.usuarios AS
SELECT * FROM usuarios;

-- Verificar backups creados
SELECT 
    'BACKUPS CREADOS EXITOSAMENTE' as resultado,
    (SELECT COUNT(*) FROM tamep_backup_20251223.contenedores_fisicos) as contenedores_backup,
    (SELECT COUNT(*) FROM tamep_backup_20251223.registro_diario) as diarios_backup,
    (SELECT COUNT(*) FROM tamep_backup_20251223.prestamos) as prestamos_backup,
    (SELECT COUNT(*) FROM tamep_backup_20251223.usuarios) as usuarios_backup;

-- =====================================================
-- SCRIPT DE RESTAURACIÓN (Solo si algo sale mal)
-- =====================================================
/*
-- DESCOMENTA SOLO EN CASO DE EMERGENCIA

-- Restaurar contenedores_fisicos
TRUNCATE TABLE contenedores_fisicos;
INSERT INTO contenedores_fisicos SELECT * FROM tamep_backup_20251223.contenedores_fisicos;

-- Restaurar registro_diario
TRUNCATE TABLE registro_diario;
INSERT INTO registro_diario SELECT * FROM tamep_backup_20251223.registro_diario;

-- Restaurar prestamos
TRUNCATE TABLE prestamos;
INSERT INTO prestamos SELECT * FROM tamep_backup_20251223.prestamos;

SELECT 'DATOS RESTAURADOS DESDE BACKUP' as resultado;
*/
