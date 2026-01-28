<?php 
ob_start(); 
$pageTitle = 'Editar / Procesar Préstamo';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📝 Editar Préstamo #<?= $prestamo['id'] ?></h2>
        <div class="header-actions">
            <a href="/prestamos" class="btn btn-secondary">← Volver al Listado</a>
        </div>
    </div>
    
    <!-- Sección Agregar -->
    <div style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
        <h4>➕ Agregar Documento</h4>
        <form action="/prestamos/agregarDetalle" method="POST" style="display: flex; gap: 10px; align-items: flex-start;">
            <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">
            
            <div style="flex: 1;">
                <select id="documento_id" name="documento_id" class="form-control" required onchange="updateInfo()">
                    <option value="">Buscar documento...</option>
                    <?php foreach ($disponibles as $doc): ?>
                        <option value="<?= $doc['id'] ?>" 
                                data-tipo="<?= htmlspecialchars($doc['tipo_documento'] ?? '') ?>"
                                data-gestion="<?= htmlspecialchars($doc['gestion'] ?? '') ?>"
                                data-comprobante="<?= htmlspecialchars($doc['nro_comprobante'] ?? '') ?>">
                            <?= htmlspecialchars($doc['tipo_documento']) ?> - G:<?= htmlspecialchars($doc['gestion']) ?> - #<?= htmlspecialchars($doc['nro_comprobante']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="doc-info" style="margin-top: 5px; font-size: 0.9em; color: #666; display:none;"></div>
            </div>
            
            <button type="submit" class="btn btn-success">Agregar a la Lista</button>
        </form>
    </div>

    <div class="alert alert-info" style="margin: 20px;">
        <strong>Instrucciones:</strong> Verifique físicamente los documentos. Marcado = EXISTE. No marcado = FALTA.
        <br>Use el botón <span class="text-danger">🗑️</span> para quitar documentos que se agregaron por error.
    </div>

    <!-- Lista y Confirmación -->
    <form action="/prestamos/confirmarProceso" method="POST" id="form-proceso" style="padding: 0 20px 20px;">
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
                        <th width="80" class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalles)): ?>
                        <tr><td colspan="5" class="text-center">No hay documentos en este préstamo</td></tr>
                    <?php else: ?>
                        <?php foreach ($detalles as $doc): ?>
                        <tr class="<?= $doc['estado'] === 'Falta' ? 'row-falta' : '' ?>">
                            <td class="text-center align-middle">
                                <?php if ($doc['estado'] !== 'Devuelto'): ?>
                                    <input type="checkbox" name="documentos[]" value="<?= $doc['id'] ?>" class="check-item" <?= $doc['estado'] === 'Prestado' ? 'checked' : 'checked' ?>>
                                <?php else: ?>
                                    ✅
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong><br>
                                <small>
                                    Gestión: <?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?> 
                                    | Nro: <?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>
                                </small>
                            </td>
                            <td>
                                 <?php if (!empty($doc['tipo_contenedor'])): ?>
                                    <?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?><br>
                                    <small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></small>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($doc['estado']) ?> 
                                (Doc: <?= htmlspecialchars($doc['estado_documento'] ?? '') ?>)
                            </td>
                            <td class="text-center align-middle">
                                <?php if ($doc['estado'] !== 'Devuelto'): ?>
                                    <button type="button" onclick="quitarDocumento(<?= $doc['id'] ?>)" class="btn btn-sm btn-outline-danger" title="Quitar de la lista">
                                        🗑️
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="detail-actions" style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary btn-lg">
                💾 Guardar Cambios / Procesar
            </button>
        </div>
    </form>
</div>

<script>
function updateInfo() {
    const select = document.getElementById('documento_id');
    const info = document.getElementById('doc-info');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        info.style.display = 'block';
        info.innerText = `Seleccionado: ${option.dataset.tipo} - ${option.dataset.comprobante}`;
    } else {
        info.style.display = 'none';
    }
}

function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.check-item');
    for(let i=0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function quitarDocumento(id) {
    if(confirm('¿Quitar este documento de la lista de préstamo? (Volverá a estar Disponible)')) {
        window.location.href = '/prestamos/quitarDetalle/' + id;
    }
}
</script>

<style>
.row-falta { background-color: #ffebee; }
.header-actions { display: flex; gap: 10px; }
</style>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
