<?php
$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "=== TIPO_DOCUMENTO (Codes) ===\n";
    $stm = $pdo->query("SELECT id, codigo, nombre FROM tipo_documento");
    $types = $stm->fetchAll();
    foreach($types as $t) {
        echo "ID: {$t['id']} -> Code: {$t['codigo']} ({$t['nombre']})\n";
    }

    echo "\n=== DOCUMENTOS (Distinct String Values) ===\n";
    $stm = $pdo->query("SELECT DISTINCT tipo_documento FROM documentos");
    $docs = $stm->fetchAll(PDO::FETCH_COLUMN);
    foreach($docs as $d) {
        echo "Value: '$d'\n";
    }
    
    // Check coverage
    echo "\n=== PROPOSED MAPPING ===\n";
    foreach($docs as $d) {
        $found = false;
        foreach($types as $t) {
            if ($t['codigo'] === $d) {
                echo "UPDATE documents SET tipo_documento_id = {$t['id']} WHERE tipo_documento = '$d';\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "WARNING: No mapping found for document type string: '$d'\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
