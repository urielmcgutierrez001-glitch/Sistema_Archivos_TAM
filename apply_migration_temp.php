<?php
require_once __DIR__ . '/public/autoload.php';
use TAMEP\Core\Database;

try {
    $db = Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/database/migration_add_ubicacion_id.sql');
    $db->getPdo()->exec($sql);
    echo "Migration applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
