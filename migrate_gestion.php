<?php
require 'public/autoload.php';
use TAMEP\Core\Database;

try {
    $db = Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/database/migration_add_gestion_to_contenedores.sql');
    $db->query($sql);
    echo "Migration applied successfully: Added 'gestion' column to 'contenedores_fisicos'.\n";
} catch (Exception $e) {
    echo "Error applying migration: " . $e->getMessage() . "\n";
}
