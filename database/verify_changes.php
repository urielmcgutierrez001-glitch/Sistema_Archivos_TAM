<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Models/BaseModel.php';
require_once __DIR__ . '/../src/Models/PrestamoHeader.php';

use TAMEP\Models\PrestamoHeader;

// Mock database connection (or usage of real one if config is loaded)
// Since we are not in the app environment (autoloader etc might be tricky), 
// let's just use the same logic as the migration script to crudely test DB access
// actually, let's try to trust the app's structure if we can load it, 
// but manual test via PDO is safer to check table existence.

$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    
    echo "DB Connection: OK\n";
    
    // Check if table exists
    $stmt = $pdo->query("SELECT count(*) FROM prestamos_encabezados");
    if ($stmt) {
        echo "Table 'prestamos_encabezados': FOUND\n";
    } else {
        echo "Table 'prestamos_encabezados': MISSING\n";
    }
    
    // Check column in prestamos
    $stmt = $pdo->query("SHOW COLUMNS FROM prestamos LIKE 'encabezado_id'");
    $col = $stmt->fetch();
    if ($col) {
        echo "Column 'encabezado_id' in 'prestamos': FOUND\n";
    } else {
        echo "Column 'encabezado_id' in 'prestamos': MISSING\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
