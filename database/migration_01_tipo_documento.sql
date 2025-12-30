-- =====================================================
-- MIGRACIÓN TAMEP - PARTE 1: CREAR TABLA TIPO_DOCUMENTO
-- Fecha: 2025-12-22
-- =====================================================

-- Paso 1: Crear tabla tipo_documento normalizada
CREATE TABLE IF NOT EXISTS tipo_documento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paso 2: Insertar tipos de documentos existentes
INSERT INTO tipo_documento (codigo, nombre, descripcion, orden) VALUES
('REGISTRO_DIARIO', 'Registro Diario', 'Registro diario de operaciones contables', 1),
('REGISTRO_INGRESO', 'Registro de Ingreso', 'Registro de ingresos percibidos', 2),
('REGISTRO_CEPS', 'Registro CEPS', 'Registro de Cuenta Especial de Pago de Servicios', 3),
('PREVENTIVOS', 'Preventivos', 'Documentos preventivos contables', 4),
('ASIENTOS_MANUALES', 'Asientos Manuales', 'Asientos contables manuales', 5),
('DIARIOS_APERTURA', 'Diarios de Apertura', 'Diarios de apertura de gestión', 6),
('REGISTRO_TRASPASO', 'Registro de Traspaso', 'Registros de traspasos entre cuentas', 7),
('HOJA_RUTA_DIARIOS', 'Hoja de Ruta - Diarios', 'Hojas de ruta para diarios contables', 8);

-- Verificar inserción
SELECT * FROM tipo_documento ORDER BY orden;
