<?php
require 'vendor/autoload.php';
use TAMEP\Core\Database;

try {
    $db = new Database();
    $columns = $db->fetchAll('DESCRIBE contenedores_fisicos');
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // Check if table exists
    try {
        $db->query("SELECT 1 FROM contenedores_fisicos LIMIT 1");
    } catch (Exception $e2) {
        echo "Table might not exist: " . $e2->getMessage() . "\n";
    }
}
