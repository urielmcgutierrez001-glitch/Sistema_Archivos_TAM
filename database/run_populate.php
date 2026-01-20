<?php
$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $sql = file_get_contents(__DIR__ . '/populate_fk.sql');
    
    // Split commands
    $commands = array_filter(array_map('trim', explode(';', $sql)));

    foreach($commands as $cmd) {
        if (!empty($cmd)) {
            echo "Executing: " . substr($cmd, 0, 50) . "...\n";
            $pdo->exec($cmd);
        }
    }
    
    echo "Done.\n";
    
    // Show results
    $res = $pdo->query("SELECT tipo_documento, tipo_documento_id, COUNT(*) as c FROM documentos GROUP BY tipo_documento, tipo_documento_id");
    foreach($res as $row) {
        echo "{$row['tipo_documento']} -> {$row['tipo_documento_id']} ({$row['c']})\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
