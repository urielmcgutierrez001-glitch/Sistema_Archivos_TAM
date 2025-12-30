<?php
/**
 * Configuración de Base de Datos
 * Sistema TAMEP - Gestión Documental
 * 
 * AUTO-DETECTA el entorno:
 * - En Clever Cloud: usa variables de entorno
 * - En local: usa localhost
 */

// Detectar si estamos en Clever Cloud
$isCleverCloud = getenv('MYSQL_ADDON_HOST') !== false;

if ($isCleverCloud) {
    // PRODUCCIÓN: Clever Cloud
    $config = [
        'host' => getenv('MYSQL_ADDON_HOST'),
        'port' => getenv('MYSQL_ADDON_PORT') ?: 3306,
        'database' => getenv('MYSQL_ADDON_DB'),
        'username' => getenv('MYSQL_ADDON_USER'),
        'password' => getenv('MYSQL_ADDON_PASSWORD'),
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
} else {
    // DESARROLLO: Localhost
    $config = [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'tamep_archivos',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
}

return $config;
