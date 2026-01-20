<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "Buscando contenedores con documentos de múltiples gestiones...\n";

// Query to find containers having documents with > 1 distinct gestion
$sql = "SELECT c.id, c.numero, c.gestion as gestion_contenedor, c.tipo_documento, COUNT(DISTINCT d.gestion) as gestiones_distintas
        FROM contenedores_fisicos c
        JOIN documentos d ON c.id = d.contenedor_fisico_id
        GROUP BY c.id, c.numero, c.gestion, c.tipo_documento
        HAVING gestiones_distintas > 1";

$mixed = $db->fetchAll($sql);

if (empty($mixed)) {
    echo "No se encontraron contenedores de PREVENTIVOS mezclados.\n";
} else {
    echo "Se encontraron " . count($mixed) . " contenedores mezclados:\n\n";
    foreach ($mixed as $m) {
        echo "Contenedor ID: {$m['id']} | Num: {$m['numero']} | Gestion(Base): {$m['gestion_contenedor']} | Tipo: {$m['tipo_documento']}\n";
        
        // Detalle de gestiones
        $details = $db->fetchAll(
            "SELECT gestion, COUNT(*) as cantidad FROM documentos WHERE contenedor_fisico_id = ? GROUP BY gestion", 
            [$m['id']]
        );
        foreach ($details as $d) {
            echo "   - Gestion {$d['gestion']}: {$d['cantidad']} docs\n";
        }
        echo "\n";
    }
}
