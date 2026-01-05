-- Script para agregar columna 'password' a la tabla usuarios
-- Ejecutar en Clever Cloud MySQL

-- Primero verificar estructura actual
DESCRIBE usuarios;

-- Agregar columna password si no existe
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL AFTER username;

-- Verificar si hay usuarios sin password hasheado
SELECT id, username, password, nombre_completo FROM usuarios;

-- Si la columna contrasena existe, migrar datos
-- UPDATE usuarios SET password = contrasena WHERE password IS NULL AND contrasena IS NOT NULL;

-- Crear un usuario admin con password hasheado si no existe
-- Password: admin123
-- Hash generado con password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (username, password, nombre_completo, rol, activo)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Administrador', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE username = 'admin');

-- Actualizar password del admin existente si no tiene
UPDATE usuarios 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin' AND (password IS NULL OR password = '');

-- Verificar resultado
SELECT id, username, password IS NOT NULL as tiene_password, nombre_completo, rol FROM usuarios;
