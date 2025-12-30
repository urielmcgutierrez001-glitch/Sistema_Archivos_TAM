<?php
// Activar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "DEBUG: Starting application...<br>";

// Verificar que public/index.php existe
if (!file_exists(__DIR__ . '/public/index.php')) {
    die('ERROR: public/index.php NOT FOUND at: ' . __DIR__ . '/public/index.php');
}

echo "DEBUG: Found public/index.php<br>";

// Cambiar al directorio public
chdir(__DIR__ . '/public');
echo "DEBUG: Changed to directory: " . getcwd() . "<br>";

// Incluir el index.php del public
echo "DEBUG: About to require public/index.php<br>";
require __DIR__ . '/public/index.php';
