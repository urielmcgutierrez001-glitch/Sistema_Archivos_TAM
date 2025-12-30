<?php
/**
 * Punto de entrada raíz
 * Redirige a public/index.php con directorio correcto
 */

// Cambiar al directorio public
chdir(__DIR__ . '/public');

// Incluir el index.php del public
require __DIR__ . '/public/index.php';
