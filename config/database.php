<?php

return [
    'host' => getenv('MYSQL_ADDON_HOST') ?: 'localhost',
    'port' => getenv('MYSQL_ADDON_PORT') ?: 3306,
    'database' => getenv('MYSQL_ADDON_DB') ?: 'tamep_archivos',
    'username' => getenv('MYSQL_ADDON_USER') ?: 'root',
    'password' => getenv('MYSQL_ADDON_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];