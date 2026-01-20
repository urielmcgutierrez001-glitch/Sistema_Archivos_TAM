<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "Buscando documentos PREVENTIVOS 2024...\n";
$sql = "SELECT id, nro_comprobante, contenedor_fisico_id FROM documentos 
        WHERE tipo_documento = 'PREVENTIVOS' AND gestion = 2024 
        AND contenedor_fisico_id IS NOT NULL 
        LIMIT 5";

$docs = $db->fetchAll($sql);

if (empty($docs)) {
    echo "No se encontraron documentos PREVENTIVOS 2024 asignados a contenedores.\n";
} else {
    foreach ($docs as $d) {
        $cid = $d['contenedor_fisico_id'];
        echo "Doc #{$d['nro_comprobante']} -> Contenedor ID: $cid\n";
        
        // Inspect this container
        $c = $db->fetchOne("SELECT * FROM contenedores_fisicos WHERE id = ?", [$cid]);
        if ($c) {
            echo "   CONTENEDOR DATA: Num: '{$c['numero']}' | Gestion: '{$c['gestion']}' | TipoTxt: '{$c['tipo_documento']}' | ID_Ref: '{$c['tipo_documento_id']}'\n";
        } else {
            echo "   CONTENEDOR ID $cid NO EXISTE en tabla contenedores_fisicos!\n";
        }
    }
}
