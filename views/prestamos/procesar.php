<?php 
ob_start(); 
$pageTitle = 'Procesar Préstamo';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📋 Procesar Préstamo #<?= $prestamo['id'] ?></h2>
        <div class="header-actions">
            <a href="/prestamos/ver/<?= $prestamo['id'] ?>" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
    
    <div class="alert alert-info" style="margin: 20px;">
        <strong>Instrucciones:</strong> Verifique físicamente los documentos. Seleccione (tick) los documentos que <strong>SÍ</strong> se encuentran y se entregarán. Los documentos <strong>NO</strong> seleccionados se marcarán como <strong>FALTA</strong>.
    </div>

    <form action="/prestamos/confirmarProceso" method="POST" id="form-proceso" style="padding: 20px;">
        <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50" class="text-center">
                            <input type="checkbox" id="check-all" onclick="toggleAll(this)" checked>
                        </th>
                        <th>Documento</th>
                        <th>Ubicación</th>
                        <th>Estado Actual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $doc): ?>
                    <tr class="<?= $doc['estado'] === 'Falta' ? 'row-falta' : '' ?>">
                        <td class="text-center">
                            <input type="checkbox" name="documentos[]" value="<?= $doc['id'] ?>" class="check-item" checked>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong><br>
                            <small>
                                Gestión: <?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?> 
                                | Nro: <?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>
                            </small>
                        </td>
                        <td>
                             <?php if ($doc['tipo_contenedor']): ?>
                                <?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?><br>
                                <small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></small>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($doc['estado_documento'] ?? 'N/A') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="detail-actions" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary btn-lg">
                ✅ Confirmar y Procesar Préstamo
            </button>
        </div>
    </form>
</div>

<style>
.row-falta {
    background-color: #ffebee;
}
.header-actions {
    display: flex;
    gap: 10px;
}
</style>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.check-item');
    for(let i=0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
