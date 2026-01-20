<?php
require_once __DIR__ . '/public/autoload.php';

use TAMEP\Core\Database;

$db = Database::getInstance();

echo "=== REPORTE DE ESTADO BASE DE DATOS LOCAL ===\n\n";

// 1. Totales Generales
$totalDocs = $db->fetchOne("SELECT COUNT(*) as t FROM documentos")['t'];
$totalCont = $db->fetchOne("SELECT COUNT(*) as t FROM contenedores_fisicos")['t'];
$totalTipos = $db->fetchOne("SELECT COUNT(*) as t FROM tipo_documento")['t'];

echo "1. TOTALES:\n";
echo "   - Documentos: " . number_format($totalDocs) . "\n";
echo "   - Contenedores: " . number_format($totalCont) . "\n";
echo "   - Tipos de Documento: $totalTipos\n\n";

// 2. Integridad de Relacion
$unassigned = $db->fetchOne("SELECT COUNT(*) as t FROM documentos WHERE contenedor_fisico_id IS NULL")['t'];
$emptyCont = $db->fetchOne("SELECT COUNT(*) as t FROM contenedores_fisicos c WHERE (SELECT COUNT(*) FROM documentos d WHERE d.contenedor_fisico_id = c.id) = 0")['t'];

echo "2. INTEGRIDAD:\n";
echo "   - Documentos SIN contenedor (Sin asignar): " . number_format($unassigned) . "\n";
echo "   - Contenedores VACIOS (Sin documentos): " . number_format($emptyCont) . "\n";

// 3. Verificacion de Contenedores Mezclados (Doble check)
$mixed = $db->fetchOne("
    SELECT COUNT(*) as t FROM (
        SELECT c.id
        FROM contenedores_fisicos c
        JOIN documentos d ON c.id = d.contenedor_fisico_id
        GROUP BY c.id
        HAVING COUNT(DISTINCT d.gestion) > 1
    ) as subquery
")['t'];

echo "   - Contenedores MEZCLADOS (Multiples años): $mixed " . ($mixed == 0 ? "✅ OK" : "❌ ATENCION") . "\n\n";

// 4. Distribucion por Tipo (Top 10)
echo "3. DISTRIBUCION DE CONTENEDORES POR TIPO:\n";
$dist = $db->fetchAll("SELECT tipo_documento, COUNT(*) as c FROM contenedores_fisicos GROUP BY tipo_documento ORDER BY c DESC LIMIT 10");
foreach ($dist as $r) {
    echo "   - {$r['tipo_documento']}: {$r['c']}\n";
}
echo "\n";

// 5. Distribucion por Gestion (Top 10 Recientes)
echo "4. DISTRIBUCION DE CONTENEDORES POR GESTION (Top 10):\n";
$gestiones = $db->fetchAll("SELECT gestion, COUNT(*) as c FROM contenedores_fisicos GROUP BY gestion ORDER BY gestion DESC LIMIT 10");
foreach ($gestiones as $r) {
    echo "   - {$r['gestion']}: {$r['c']}\n";
}
echo "\n";

// 6. Muestra de Tipos de Documento
echo "5. TIPOS DE DOCUMENTO REGISTRADOS:\n";
$tipos = $db->fetchAll("SELECT id, codigo, nombre FROM tipo_documento ORDER BY orden ASC");
foreach ($tipos as $t) {
    echo "   - ID {$t['id']}: {$t['codigo']} ({$t['nombre']})\n";
}

echo "\n=== FIN DEL REPORTE ===\n";
