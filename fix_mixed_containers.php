<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "Iniciando reparación de contenedores mezclados...\n";

// 1. Obtener contenedores mezclados
$sql = "SELECT c.id, c.numero, c.gestion as gestion_contenedor, c.tipo_documento, c.tipo_documento_id, c.tipo_contenedor, c.ubicacion_id, COUNT(DISTINCT d.gestion) as gestiones_distintas
        FROM contenedores_fisicos c
        JOIN documentos d ON c.id = d.contenedor_fisico_id
        GROUP BY c.id, c.numero, c.gestion, c.tipo_documento, c.tipo_documento_id, c.tipo_contenedor, c.ubicacion_id
        HAVING gestiones_distintas > 1";

$mixed = $db->fetchAll($sql);

foreach ($mixed as $m) {
    echo "Procesando Contenedor ID: {$m['id']} (Base: {$m['gestion_contenedor']}, Num: {$m['numero']})...\n";
    
    // Obtener las gestiones 'intrusas' (diferentes a la del contenedor)
    // OJO: Si el contenedor dice 2018, pero tiene docs de 2018 y 2022, movemos los de 2022.
    // Si el contenedor dice 2018 pero SOLO tiene docs de 2022 (caso raro), deberiamos actualizar el contenedor? 
    // Asumiremos que movemos lo que NO coincida con gestion_contenedor.
    
    $gestiones = $db->fetchAll(
        "SELECT DISTINCT gestion FROM documentos WHERE contenedor_fisico_id = ? AND gestion != ?",
        [$m['id'], $m['gestion_contenedor']]
    );
    
    foreach ($gestiones as $gRow) {
        $yearToMove = $gRow['gestion'];
        echo "   -> Encontrados documentos del año $yearToMove. Moviendo...\n";
        
        // 1. Buscar si existe contenedor destino
        $destContainer = $db->fetchOne(
            "SELECT id FROM contenedores_fisicos WHERE numero = ? AND gestion = ? AND tipo_documento_id = ?",
            [$m['numero'], $yearToMove, $m['tipo_documento_id']]
        );
        
        $destId = null;
        
        if ($destContainer) {
            $destId = $destContainer['id'];
            echo "      Contenedor destino ya existe (ID $destId).\n";
        } else {
            echo "      Creando nuevo contenedor para $yearToMove...\n";
            $db->query(
                "INSERT INTO contenedores_fisicos (tipo_contenedor, numero, gestion, tipo_documento, tipo_documento_id, ubicacion_id) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $m['tipo_contenedor'],
                    $m['numero'],
                    $yearToMove,
                    $m['tipo_documento'],
                    $m['tipo_documento_id'],
                    $m['ubicacion_id']
                ]
            );
            $destId = $db->lastInsertId();
            echo "      Nuevo contenedor creado (ID $destId).\n";
        }
        
        // 2. Mover documentos
        $db->query(
            "UPDATE documentos SET contenedor_fisico_id = ? WHERE contenedor_fisico_id = ? AND gestion = ?",
            [$destId, $m['id'], $yearToMove]
        );
        
        // $count = $db->rowCount(); 
        echo "      Documentos movidos.\n";
    }
}

echo "Reparación completada.\n";
