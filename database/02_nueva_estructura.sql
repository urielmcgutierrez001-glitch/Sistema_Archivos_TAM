-- =====================================================
-- NUEVA ESTRUCTURA DE TABLAS
-- Mejoras al esquema para clasificación de contenedores
-- =====================================================

SET SQL_SAFE_UPDATES = 0;

-- =====================================================
-- 1. TABLA: tipos_contenedor (Catálogo)
-- =====================================================
CREATE TABLE IF NOT EXISTS tipos_contenedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL COMMENT 'AMARRO, LIBRO',
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo de tipos de contenedores físicos';

-- Poblar tipos de contenedor
INSERT IGNORE INTO tipos_contenedor (codigo, nombre, descripcion) VALUES
('AMARRO', 'Amarro', 'Contenedor tipo amarro para documentos'),
('LIBRO', 'Libro', 'Contenedor tipo libro para documentos');

-- =====================================================
-- 2. TABLA: clasificacion_contenedor_documento
-- =====================================================
-- Relación N:N entre contenedores y tipos de documentos
-- Permite que un contenedor tenga múltiples tipos de docs en diferentes gestiones

CREATE TABLE IF NOT EXISTS clasificacion_contenedor_documento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenedor_id INT NOT NULL,
    tipo_documento VARCHAR(50) NOT NULL COMMENT 'REGISTRO_DIARIO, REGISTRO_INGRESO, etc.',
    gestion_desde INT COMMENT 'Año desde el cual contiene este tipo',
    gestion_hasta INT COMMENT 'Año hasta el cual contiene este tipo',
    cantidad_documentos INT DEFAULT 0 COMMENT 'Cantidad de documentos de este tipo',
    observaciones TEXT,
    fecha_clasificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (contenedor_id) REFERENCES contenedores_fisicos(id) ON DELETE CASCADE,
    
    INDEX idx_contenedor (contenedor_id),
    INDEX idx_tipo_doc (tipo_documento),
    INDEX idx_gestion (gestion_desde, gestion_hasta),
    INDEX idx_combo (contenedor_id, tipo_documento),
    
    UNIQUE KEY uk_contenedor_tipo_gestion (contenedor_id, tipo_documento, gestion_desde, gestion_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Clasificación de qué tipos de documentos contiene cada contenedor por gestión';

-- =====================================================
-- 3. MODIFICAR: contenedores_fisicos
-- =====================================================

-- Primero verificar si las columnas ya existen y crearlas si no
SET @dbname = DATABASE();
SET @tablename = 'contenedores_fisicos';

-- Agregar tipo_contenedor_id si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = @tablename 
    AND COLUMN_NAME = 'tipo_contenedor_id'
);

SET @sql_add_tipo_id = IF(@column_exists = 0,
    'ALTER TABLE contenedores_fisicos ADD COLUMN tipo_contenedor_id INT AFTER tipo_contenedor',
    'SELECT "Column tipo_contenedor_id already exists" as msg');
PREPARE stmt FROM @sql_add_tipo_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar numero_original si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = @tablename 
    AND COLUMN_NAME = 'numero_original'
);

SET @sql_add_numero_orig = IF(@column_exists = 0,
    'ALTER TABLE contenedores_fisicos ADD COLUMN numero_original VARCHAR(50) AFTER numero',
    'SELECT "Column numero_original already exists" as msg');
PREPARE stmt FROM @sql_add_numero_orig;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar FK a tipos_contenedor si no existe
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND CONSTRAINT_NAME = 'fk_tipo_contenedor'
);

SET @sql_add_fk = IF(@fk_exists = 0,
    'ALTER TABLE contenedores_fisicos ADD CONSTRAINT fk_tipo_contenedor FOREIGN KEY (tipo_contenedor_id) REFERENCES tipos_contenedor(id)',
    'SELECT "FK fk_tipo_contenedor already exists" as msg');
PREPARE stmt FROM @sql_add_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Poblar tipo_contenedor_id basándose en tipo_contenedor (VARCHAR)
UPDATE contenedores_fisicos cf
INNER JOIN tipos_contenedor tc ON cf.tipo_contenedor = tc.codigo
SET cf.tipo_contenedor_id = tc.id
WHERE cf.tipo_contenedor_id IS NULL;

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

SELECT 'NUEVA ESTRUCTURA CREADA' as resultado;

SELECT 'Tipos de Contenedor' as tabla, COUNT(*) as registros FROM tipos_contenedor
UNION ALL
SELECT 'Contenedores con tipo_id asignado', COUNT(*) FROM contenedores_fisicos WHERE tipo_contenedor_id IS NOT NULL;

-- Mostrar estructura actualizada
SHOW CREATE TABLE tipos_contenedor;
SHOW CREATE TABLE clasificacion_contenedor_documento;
DESC contenedores_fisicos;

SET SQL_SAFE_UPDATES = 1;
