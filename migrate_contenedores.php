<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "Iniciando migración de tipos de documento en contenedores...\n";

// 1. Obtener mapeo de Tipos
$tipos = $db->fetchAll("SELECT id, codigo FROM tipo_documento");
$map = [];
foreach ($tipos as $t) {
    echo "Tipo encontrado: ID {$t['id']} => '{$t['codigo']}'\n";
    $map[$t['codigo']] = $t['id'];
}

// 2. Obtener contenedores con tipo_documento_id NULL pero con texto
$contenedores = $db->fetchAll("SELECT id, tipo_documento FROM contenedores_fisicos WHERE tipo_documento_id IS NULL AND tipo_documento IS NOT NULL");

$count = 0;
foreach ($contenedores as $c) {
    // Normalizar string (algunos pueden tener espacios)
    $codigo = trim($c['tipo_documento']);
    
    if (isset($map[$codigo])) {
        $newId = $map[$codigo];
        $db->query("UPDATE contenedores_fisicos SET tipo_documento_id = ? WHERE id = ?", [$newId, $c['id']]);
        $count++;
    } else {
        echo "WARNING: Contenedor #{$c['id']} tiene tipo desconocido: '$codigo'\n";
    }
}

echo "Migración completada. {$count} contenedores actualizados.\n";
