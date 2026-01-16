<?php
require 'public/index.php'; // Takes care of autoloading and config
// Actually public/index.php dispatches route, might be better to just require autoload
// But public/autoload.php is custom.
// Let's try to just use raw PDO if needed or include what's needed.

require_once 'public/autoload.php';
use TAMEP\Core\Database;

try {
    $db = Database::getInstance();
    $columns = $db->fetchAll("DESCRIBE contenedores_fisicos");
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
