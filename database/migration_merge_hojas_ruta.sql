-- 1. Asegurar que el Tipo de Documento exista y tenga el esquema correcto
INSERT INTO tipo_documento (codigo, nombre, descripcion, activo, orden, esquema_atributos)
VALUES (
    'HOJA_RUTA_DIARIOS', 
    'Hoja de Ruta de Diarios', 
    'Registro de Hojas de Ruta migrado', 
    1, 
    2,
    '{
        "standard_fields": {},
        "custom_fields": [
            {"key": "nro_comprobante_diario", "label": "Nro Comprobante Diario", "type": "text"},
            {"key": "gestion_hr", "label": "Gestión HR", "type": "number"},
            {"key": "conam", "label": "CONAM", "type": "text"},
            {"key": "rubro", "label": "Rubro", "type": "text"},
            {"key": "interesado", "label": "Interesado", "type": "text"},
            {"key": "lugar_archivo", "label": "Lugar Archivo", "type": "text"}
        ]
    }'
)
ON DUPLICATE KEY UPDATE 
    esquema_atributos = VALUES(esquema_atributos);

-- 2. Migrar datos de registro_hojas_ruta a documentos
INSERT IGNORE INTO documentos (
    tipo_documento,
    tipo_documento_id,
    gestion,
    nro_comprobante,
    contenedor_fisico_id,
    observaciones,
    estado_documento,
    fecha_creacion,
    fecha_modificacion, 
    atributos_extra
)
SELECT 
    'HOJA_RUTA_DIARIOS',
    (SELECT id FROM tipo_documento WHERE codigo = 'HOJA_RUTA_DIARIOS' LIMIT 1),
    gestion,
    nro_hoja_ruta, -- Mapear Hoja Ruta a Nro Comprobante principal
    contenedor_fisico_id,
    observaciones,
    CASE 
        WHEN estado_perdido = 1 THEN 'FALTA'
        WHEN activo = 1 THEN 'DISPONIBLE'
        ELSE 'ANULADO'
    END,
    NOW(),
    NOW(),
    JSON_OBJECT(
        'nro_comprobante_diario', nro_comprobante_diario,
        'gestion_hr', gestion_hr,
        'conam', conam,
        'rubro', rubro,
        'interesado', interesado,
        'lugar_archivo', lugar_archivo
    )
FROM registro_hojas_ruta;

-- 3. (Opcional) Eliminar tabla vieja
-- DROP TABLE registro_hojas_ruta;
