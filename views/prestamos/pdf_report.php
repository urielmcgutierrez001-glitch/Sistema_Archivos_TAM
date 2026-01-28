<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamo #<?= $prestamo['id'] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            position: relative;
        }
        .logo {
            position: absolute;
            top: -15px;
            right: 0;
            width: 60px;
            height: auto;
        }
        .info-box {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
            background: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }
        th {
            background-color: #e3e3e3;
            font-weight: bold;
        }
        .text-left {
             text-align: left;
        }
        @media print {
            .no-print {
                display: none;
            }
            body { 
                padding: 0; 
                -webkit-print-color-adjust: exact; 
            }
            th {
                background-color: #e3e3e3 !important;
            }
        }
        .signatures-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .section-title {
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 10px; 
            text-decoration: underline;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px; /* Space for signature */
        }
        .signature-box {
            border-top: 1px solid #000;
            width: 40%;
            text-align: center;
            padding-top: 5px;
        }
        .observaciones-generales {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
            min-height: 50px;
        }
        .total-box {
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.close()">✖ Cerrar</button>
    </div>

    <div class="header">
        <h2>ACTA DE PRÉSTAMO DE DOCUMENTOS</h2>
        <img src="/assets/img/logo-tamep.png" alt="Logo" class="logo">
        <p>Fecha: <?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?> | ID Préstamo: <?= $prestamo['id'] ?></p>
    </div>

    <div class="info-box">
        <strong>SOLICITANTE:</strong> <?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?> (<?= htmlspecialchars($prestamo['unidad_nombre'] ?? '') ?>)<br>
        <strong>FECHA DEVOLUCIÓN ESTIMADA:</strong> <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) ?><br>
        <strong>OBSERVACIONES (SOLICITUD):</strong> <?= htmlspecialchars($prestamo['observaciones'] ?? 'Ninguna') ?>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">GESTION</th>
                <th rowspan="2">NRO COMPROBANTE</th>
                <th colspan="4">UBICACIÓN</th>
                <th rowspan="2">TIPO DOCUMENTO</th>
                <th rowspan="2" style="font-size: 14px; width: 30px;">☑</th>
                <th rowspan="2">OBSERVACIONES</th>
            </tr>
            <tr>
                <th colspan="2">CONTENEDOR</th>
                <th>NRO</th>
                <th>UBICACIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $conteoTipos = [];
            $conteoA = 0;
            $conteoL = 0;

            foreach ($detalles as $index => $doc): 
                // Document Count by Type
                $docTipo = $doc['tipo_documento'] ?? 'OTROS';
                if (!isset($conteoTipos[$docTipo])) {
                    $conteoTipos[$docTipo] = 0;
                }
                $conteoTipos[$docTipo]++;

                // Container Logic
                $tipoContenedor = strtoupper($doc['tipo_contenedor'] ?? '');
                $isAmarro = (strpos($tipoContenedor, 'AMARRO') !== false);
                $isLibro = (strpos($tipoContenedor, 'LIBRO') !== false);

                if ($isAmarro) $conteoA++;
                if ($isLibro) $conteoL++;
                
                $estadoDoc = $doc['estado_documento'] ?? '';
                $obs = '';
                // Only show relevant negative states
                if (in_array($estadoDoc, ['FALTA', 'ANULADO', 'NO UTILIZADO'])) {
                    $obs = $estadoDoc;
                } elseif ($estadoDoc === 'PRESTADO') {
                     $obs = '';
                }
            ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($doc['gestion'] ?? '') ?></td>
                <td><?= htmlspecialchars($doc['nro_comprobante'] ?? '-') ?></td>
                
                <!-- Contenedor Split -->
                <td style="width: 20px;"><?= $isAmarro ? 'A' : '' ?></td>
                <td style="width: 20px;"><?= $isLibro ? 'L' : '' ?></td>
                
                <td><?= htmlspecialchars($doc['contenedor_numero'] ?? '-') ?></td>
                <td><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></td>
                
                <td class="text-left" style="font-size: 9px;"><?= htmlspecialchars($doc['tipo_documento'] ?? '') ?></td>
                <td></td>
                <td style="color: red; font-weight: bold;"><?= htmlspecialchars($obs) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f0;">
                <td colspan="3" style="text-align: right; font-weight: bold;">TOTALES:</td>
                <td style="font-weight: bold;"><?= $conteoA > 0 ? $conteoA : '' ?></td>
                <td style="font-weight: bold;"><?= $conteoL > 0 ? $conteoL : '' ?></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>

    <div class="total-box">
        <?php foreach ($conteoTipos as $tipo => $cantidad): ?>
            <div>TOTAL <?= htmlspecialchars($tipo) ?>: <?= $cantidad ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Primera Sección: Entrega -->
    <div class="signatures-section">
        <div class="section-title">ENTREGA DE DOCUMENTOS PRESTADOS</div>
        <div class="signatures">
            <div class="signature-box">
                Entrega Conforme<br>
                <strong><?= htmlspecialchars($prestamo['usuario_nombre'] ?? 'Archivo Central') ?></strong>
            </div>
            <div class="signature-box">
                Recibe Conforme<br>
                <strong><?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?></strong>
            </div>
        </div>
        
        <div class="observaciones-generales">
            <strong>Observaciones de Documentos (Entrega):</strong><br>
        </div>
    </div>

    <!-- Segunda Sección: Devolución -->
    <div class="signatures-section" style="margin-top: 60px;">
        <div class="section-title">DEVOLUCIÓN DE DOCUMENTOS</div>
        <div class="signatures">
            <div class="signature-box">
                Recibe Conforme (Devolución)<br>
                <strong><?= htmlspecialchars($prestamo['usuario_nombre'] ?? 'Archivo Central') ?></strong>
            </div>
            <div class="signature-box">
                Entrega Conforme (Devolución)<br>
                <strong><?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?></strong>
            </div>
        </div>

        <div class="observaciones-generales">
            <strong>Observaciones de Documentos (Devolución):</strong><br>
        </div>
    </div>

</body>
</html>
