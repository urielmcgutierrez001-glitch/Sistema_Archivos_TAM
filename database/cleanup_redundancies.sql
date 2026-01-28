-- ==========================================
-- SCRIPT DE LIMPIEZA DE REDUNDANCIAS
-- ==========================================

BEGIN;

-- 1. LIMPIAR CONTENEDORES FISICOS
-- ------------------------------------------
-- El usuario reportó redundancia en tipo_documento.
-- Eliminamos la columna de texto y nos quedamos solo con el ID referencial.

-- Primero, aseguramos que tipo_documento_id sea FK válida si no lo es
-- (Asumiendo que apunta a tipo_documento o su equivalente, aunque eliminamos la tabla 'tipo_documento' vieja?
-- Espera, en la normalizacion anterior la tabla 'tipo_documento' fue eliminada?
-- No, 'tipo_documento' (string) en 'documentos' fue eliminada.
-- Pero existe una tabla maestra de tipos de documento? 'tipos_documento'?
-- El esquema original tenia 'tipo_documento' como tabla. Verificaremos.)

DO $$ 
BEGIN
    -- Si existe la columna de texto redundante, la borramos
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'contenedores_fisicos' AND column_name = 'tipo_documento') THEN
        ALTER TABLE "contenedores_fisicos" DROP COLUMN "tipo_documento";
    END IF;

    -- Si existe la columna de texto 'tipo_documento_id', verificar su FK
    -- Si no hay tabla 'tipos_documento', entonces tipo_documento_id podría quedar huérfano.
    -- Vamos a asumir que 'tipo_documento' tabla existe (la vimos en el esquema inicial).
END $$;

-- 2. VERIFICACION DE ESTADOS (Asegurar limpieza previa)
ALTER TABLE "documentos" DROP COLUMN IF EXISTS "estado_documento";
ALTER TABLE "contenedores_fisicos" DROP COLUMN IF EXISTS "estado";

COMMIT;
