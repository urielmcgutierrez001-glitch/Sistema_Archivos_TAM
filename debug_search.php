<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "Inspeccionando contenedores numero 1 gestion 2024...\n\n";

$sql = "SELECT c.id, c.numero, c.gestion, c.tipo_documento, c.tipo_documento_id, t.codigo as tipo_codigo_join 
        FROM contenedores_fisicos c 
        LEFT JOIN tipo_documento t ON c.tipo_documento_id = t.id
        WHERE c.numero = '1' AND c.gestion = '2024'";

$results = $db->fetchAll($sql);

if (empty($results)) {
    echo "No se encontraron contenedores con numero=1 y gestion=2024.\n";
    // Try LIKE search for number just in case
    $sql2 = "SELECT id, numero, gestion, tipo_documento FROM contenedores_fisicos WHERE numero LIKE '%1%' AND gestion = '2024'";
    $results2 = $db->fetchAll($sql2);
    echo "Busqueda por LIKE '%1%': " . count($results2) . " encontrados.\n";
    print_r($results2);
} else {
    foreach ($results as $r) {
        echo "ID: {$r['id']} | Tipo(TXT): '{$r['tipo_documento']}' | ID_Ref: {$r['tipo_documento_id']} | Codigo_Join: '{$r['tipo_codigo_join']}'\n";
    }
}

echo "\nVerificando códigos en tabla tipo_documento:\n";
$tipos = $db->fetchAll("SELECT * FROM tipo_documento WHERE codigo LIKE '%PREV%' OR nombre LIKE '%PREV%'");
print_r($tipos);
