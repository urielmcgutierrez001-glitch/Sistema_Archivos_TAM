-- populate_fk.sql

-- 1. REGISTRO_DIARIO (ID 1)
UPDATE documentos SET tipo_documento_id = 1 WHERE tipo_documento = 'REGISTRO_DIARIO';

-- 2. REGISTRO_INGRESO (ID 2)
UPDATE documentos SET tipo_documento_id = 2 WHERE tipo_documento = 'REGISTRO_INGRESO';

-- 3. REGISTRO_CEPS (ID 3)
UPDATE documentos SET tipo_documento_id = 3 WHERE tipo_documento = 'REGISTRO_CEPS';

-- 4. PREVENTIVOS (ID 4)
UPDATE documentos SET tipo_documento_id = 4 WHERE tipo_documento = 'PREVENTIVOS';

-- 5. ASIENTOS_MANUALES (ID 5)
UPDATE documentos SET tipo_documento_id = 5 WHERE tipo_documento = 'ASIENTOS_MANUALES';

-- 6. DIARIOS_APERTURA (ID 6)
UPDATE documentos SET tipo_documento_id = 6 WHERE tipo_documento = 'DIARIOS_APERTURA';

-- 7. REGISTRO_TRASPASO (ID 7)
UPDATE documentos SET tipo_documento_id = 7 WHERE tipo_documento = 'REGISTRO_TRASPASO';

-- 8. HOJA_RUTA_DIARIOS (ID 8) -> Note: Duplicate code exists (ID 8 and 12). Using 8 as found in check.
UPDATE documentos SET tipo_documento_id = 8 WHERE tipo_documento = 'HOJA_RUTA_DIARIOS';

-- Verify counts
SELECT tipo_documento, tipo_documento_id, COUNT(*) as count 
FROM documentos 
GROUP BY tipo_documento, tipo_documento_id;
