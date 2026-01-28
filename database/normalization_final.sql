-- ==========================================
-- SCRIPT DE NORMALIZACIÓN FINAL (Estrategia User)
-- ==========================================

BEGIN;

-- 1. CREAR TABLA MAESTRA DE ESTADOS
-- ------------------------------------------
DROP TABLE IF EXISTS "estados" CASCADE;

CREATE TABLE "estados" (
    "id" SERIAL PRIMARY KEY,
    "nombre" VARCHAR(50) NOT NULL UNIQUE,
    "descripcion" TEXT,
    "activo" SMALLINT DEFAULT 1
);

-- Insertar los 5 estados definidos
INSERT INTO "estados" ("nombre") VALUES 
('DISPONIBLE'), 
('FALTA'), 
('ANULADO'), 
('NO UTILIZADO'),
('PRESTADO')
ON CONFLICT ("nombre") DO NOTHING;

-- Insertar cualquier otro estado que exista en documentos (por seguridad)
INSERT INTO "estados" ("nombre")
SELECT DISTINCT "estado_documento" 
FROM "documentos" 
WHERE "estado_documento" IS NOT NULL
ON CONFLICT ("nombre") DO NOTHING;


-- 2. NORMALIZAR DOCUMENTOS
-- ------------------------------------------
ALTER TABLE "documentos" ADD COLUMN IF NOT EXISTS "estado_documento_id" INTEGER;

-- Migrar datos (String -> ID)
UPDATE "documentos" d
SET "estado_documento_id" = e.id
FROM "estados" e
WHERE d."estado_documento" = e."nombre";

-- Asignar DISPONIBLE por defecto si es NULL
UPDATE "documentos" 
SET "estado_documento_id" = (SELECT id FROM "estados" WHERE nombre='DISPONIBLE')
WHERE "estado_documento_id" IS NULL;

-- Crear FK
ALTER TABLE "documentos" 
ADD CONSTRAINT "fk_documentos_estado" 
FOREIGN KEY ("estado_documento_id") REFERENCES "estados"("id");


-- 3. MEJORAR PRESTAMOS (Estado Anterior)
-- ------------------------------------------
-- Esta columna guardará qué estado tenía el documento antes de prestarse
-- para poder restaurarlo correctamente al devolverlo (ej: devolver a 'FALTA' si estaba faltando).
ALTER TABLE "prestamos" ADD COLUMN IF NOT EXISTS "estado_anterior_id" INTEGER;

-- Crear FK
ALTER TABLE "prestamos" 
ADD CONSTRAINT "fk_prestamos_estado_anterior" 
FOREIGN KEY ("estado_anterior_id") REFERENCES "estados"("id");


-- 4. REFACTORIZAR CONTENEDORES (Tipo)
-- ------------------------------------------
-- Asegurar que todos los tipos textuales existan en la tabla maestra
INSERT INTO "tipos_contenedor" ("codigo", "descripcion")
SELECT DISTINCT "tipo_contenedor", 'Migrado automáticamente'
FROM "contenedores_fisicos"
WHERE "tipo_contenedor" NOT IN (SELECT "codigo" FROM "tipos_contenedor");

ALTER TABLE "contenedores_fisicos" ADD COLUMN IF NOT EXISTS "tipo_contenedor_id_new" INTEGER;

-- Migrar datos
UPDATE "contenedores_fisicos" c
SET "tipo_contenedor_id_new" = t.id
FROM "tipos_contenedor" t
WHERE c."tipo_contenedor" = t."codigo";

-- Crear FK
ALTER TABLE "contenedores_fisicos" 
ADD CONSTRAINT "fk_contenedores_tipo_new" 
FOREIGN KEY ("tipo_contenedor_id_new") REFERENCES "tipos_contenedor"("id");


-- 5. LIMPIEZA FINAL (Eliminar columnas redundantes)
-- ------------------------------------------
ALTER TABLE "documentos" DROP COLUMN IF EXISTS "estado_documento";
ALTER TABLE "documentos" DROP COLUMN IF EXISTS "tipo_documento"; -- Eliminado por solicitud previa

ALTER TABLE "contenedores_fisicos" DROP COLUMN IF EXISTS "tipo_contenedor";
ALTER TABLE "contenedores_fisicos" RENAME COLUMN "tipo_contenedor_id_new" TO "tipo_contenedor_id";

COMMIT;
