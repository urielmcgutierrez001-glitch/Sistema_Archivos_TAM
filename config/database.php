<?php
/**
 * Configuración de Base de Datos
 * Sistema TAMEP - Gestión Documental
 * 
 * Configurado para usar SIEMPRE Clever Cloud MySQL
 * (funciona tanto en desarrollo local como en producción)
 */

return [
    'host' => 'bf7yz05jw1xmnb2vukrs-mysql.services.clever-cloud.com',
    'port' => 3306,
    'database' => 'bf7yz05jw1xmnb2vukrs',
    'username' => 'uh5uxh0yxbs9cxva',
    'password' => 'HdTIK6C8X5M5qsQUTXoE',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
