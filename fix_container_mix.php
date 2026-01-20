<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "Iniciando corrección de contenedores mezclados (2019 vs 2024)...\n";

// 1. Identificar el contenedor "problemático" (Amarro 1, 2019, Preventivos)
// Usamos el ID que encontré en el debug anterior: 9253
$idContenedor2019 = 9253;
$c2019 = $db->fetchOne("SELECT * FROM contenedores_fisicos WHERE id = ?", [$idContenedor2019]);

if (!$c2019) {
    die("Error: No se encontró el contenedor ID $idContenedor2019\n");
}

echo "Contenedor Original: ID {$c2019['id']} | Num: {$c2019['numero']} | Gestion: {$c2019['gestion']} | Tipo: {$c2019['tipo_documento']}\n";

// 2. Verificar/Crear el contenedor correcto para 2024
// Buscamos si ya existe uno para 2024 con los mismos datos
$tipoDocId = $c2019['tipo_documento_id']; // ID 4 (Preventivos)
$numero = $c2019['numero']; // 1
$tipoContenedor = $c2019['tipo_contenedor']; // AMARRO (asumo)

$c2024 = $db->fetchOne(
    "SELECT * FROM contenedores_fisicos WHERE numero = ? AND gestion = '2024' AND tipo_documento_id = ?", 
    [$numero, $tipoDocId]
);

$idContenedor2024 = null;

if ($c2024) {
    echo "Ya existe el contenedor 2024: ID {$c2024['id']}\n";
    $idContenedor2024 = $c2024['id'];
} else {
    echo "Creando contenedor para 2024...\n";
    $db->query(
        "INSERT INTO contenedores_fisicos (tipo_contenedor, numero, gestion, tipo_documento, tipo_documento_id, ubicacion_id) VALUES (?, ?, ?, ?, ?, ?)",
        [
            $c2019['tipo_contenedor'], 
            $c2019['numero'], 
            '2024', 
            $c2019['tipo_documento'], 
            $c2019['tipo_documento_id'],
            $c2019['ubicacion_id']
        ]
    );
    $idContenedor2024 = $db->lastInsertId();
    echo "Nuevo contenedor creado: ID $idContenedor2024\n";
}

// 3. Mover los documentos de 2024 a este nuevo contenedor
echo "Moviendo documentos de gestion 2024 del contenedor $idContenedor2019 al $idContenedor2024...\n";

// Contar afectados
$count = $db->fetchOne(
    "SELECT COUNT(*) as total FROM documentos WHERE contenedor_fisico_id = ? AND gestion = 2024", 
    [$idContenedor2019]
);
echo "Documentos a mover: {$count['total']}\n";

if ($count['total'] > 0) {
    $db->query(
        "UPDATE documentos SET contenedor_fisico_id = ? WHERE contenedor_fisico_id = ? AND gestion = 2024",
        [$idContenedor2024, $idContenedor2019]
    );
    echo "Actualización completada.\n";
} else {
    echo "No se encontraron documentos para mover.\n";
}

// 4. Verificar que los de 2019 sigan ahi
$count2019 = $db->fetchOne(
    "SELECT COUNT(*) as total FROM documentos WHERE contenedor_fisico_id = ? AND gestion = 2019", 
    [$idContenedor2019]
);
echo "Documentos restantes en contenedor 2019: {$count2019['total']}\n";
