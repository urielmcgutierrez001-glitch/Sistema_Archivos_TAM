<?php
// Fix DB Columns directly using App Config
try {
    $config = require __DIR__ . '/config/database.php';
    
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "Connected to database: {$config['database']}\n";
    
    // Check columns
    $stmt = $pdo->query("SHOW COLUMNS FROM prestamos_encabezados");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns: " . implode(', ', $columns) . "\n";
    
    // Add unidad_area_id if missing
    if (!in_array('unidad_area_id', $columns)) {
        echo "Adding unidad_area_id...\n";
        $pdo->exec("ALTER TABLE prestamos_encabezados ADD COLUMN unidad_area_id INT NULL AFTER usuario_id");
        echo "unidad_area_id added.\n";
    } else {
        echo "unidad_area_id already exists.\n";
    }
    
    // Add nombre_prestatario if missing
    if (!in_array('nombre_prestatario', $columns)) {
        echo "Adding nombre_prestatario...\n";
        $pdo->exec("ALTER TABLE prestamos_encabezados ADD COLUMN nombre_prestatario VARCHAR(255) NULL AFTER unidad_area_id");
        echo "nombre_prestatario added.\n";
    } else {
        echo "nombre_prestatario already exists.\n";
    }
    
    // Add FK
    try {
        $pdo->exec("ALTER TABLE prestamos_encabezados ADD CONSTRAINT fk_prestamo_unidad FOREIGN KEY (unidad_area_id) REFERENCES ubicaciones(id)");
        echo "FK added.\n";
    } catch (Exception $e) {
        echo "FK might already exist or error: " . $e->getMessage() . "\n";
    }

    echo "Done.\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
