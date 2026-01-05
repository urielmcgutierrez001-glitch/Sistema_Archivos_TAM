<?php
// Load config
$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database.\n";

    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/001_add_loan_headers.sql');

    // Execute multiple statements
    // PDO::exec doesn't handle multiple statements well in all drivers, so we split by semicolon for safety
    // or just try to run it if the driver allows. Let's try splitting.
    
    // Actually, let's just run the CREATE first
    $createSql = "CREATE TABLE IF NOT EXISTS prestamos_encabezados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        fecha_prestamo DATE NOT NULL,
        fecha_devolucion_esperada DATE NOT NULL,
        observaciones TEXT,
        estado VARCHAR(20) DEFAULT 'Prestado',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createSql);
    echo "Table prestamos_encabezados created or exists.\n";

    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM prestamos LIKE 'encabezado_id'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $alterSql = "ALTER TABLE prestamos ADD COLUMN encabezado_id INT NULL AFTER id;
                     ALTER TABLE prestamos ADD CONSTRAINT fk_prestamo_encabezado FOREIGN KEY (encabezado_id) REFERENCES prestamos_encabezados(id);";
        $pdo->exec($alterSql);
        echo "Column encabezado_id added to prestamos.\n";
    } else {
        echo "Column encabezado_id already exists.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
