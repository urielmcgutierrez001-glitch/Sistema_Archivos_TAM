<?php 
ob_start(); 
$pageTitle = 'Editar Documento';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>✏️ Editar Documento</h2>
        <a href="/catalogacion/ver/<?= $documento['id'] ?>" class="btn btn-secondary">← Volver al Detalle</a>
    </div>
    
    <form method="POST" action="/catalogacion/actualizar/<?= $documento['id'] ?>" class="document-form">
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento <span class="required">*</span></label>
                <select id="tipo_documento" name="tipo_documento" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tiposDocumento as $td): ?>
                        <option value="<?= $td['codigo'] ?>" <?= ($documento['tipo_documento'] ?? '') === $td['codigo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($td['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión <span class="required">*</span></label>
                <input type="number" id="gestion" name="gestion" class="form-control" 
                       value="<?= htmlspecialchars($documento['gestion'] ?? '') ?>" 
                       min="2000" max="<?= date('Y') + 1 ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="nro_comprobante">Número de Comprobante <span class="required">*</span></label>
                <input type="text" id="nro_comprobante" name="nro_comprobante" class="form-control" 
                       value="<?= htmlspecialchars($documento['nro_comprobante'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="codigo_abc">Código ABC</label>
                <input type="text" id="codigo_abc" name="codigo_abc" class="form-control" 
                       value="<?= htmlspecialchars($documento['codigo_abc'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="filtro_ubicacion">Filtrar por Ubicación</label>
                <select id="filtro_ubicacion" class="form-control" onchange="filtrarContenedores()">
                    <option value="">Todas las ubicaciones</option>
                    <?php foreach ($ubicaciones as $ub): ?>
                        <option value="<?= $ub['id'] ?>"><?= htmlspecialchars($ub['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="contenedor_fisico_id">Contenedor Físico (Libro/Amarro)</label>
                <div style="display:flex; gap:5px;">
                    <select id="contenedor_fisico_id" name="contenedor_fisico_id" class="form-control">
                        <option value="">Sin asignar</option>
                        <?php foreach ($contenedores as $cont): ?>
                            <option value="<?= $cont['id'] ?>" 
                                    data-ubicacion="<?= $cont['ubicacion_id'] ?? '' ?>"
                                    <?= ($documento['contenedor_fisico_id'] ?? '') == $cont['id'] ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($cont['tipo_documento_codigo'] ?? '???') ?>] <?= htmlspecialchars($cont['gestion'] ?? '') ?> <?= htmlspecialchars($cont['tipo_contenedor'] ?? '') ?> #<?= htmlspecialchars($cont['numero'] ?? '') ?> <?= !empty($cont['codigo_abc']) ? '('.htmlspecialchars($cont['codigo_abc']).')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-success" onclick="abrirModalCrearContenedor('contenedor_fisico_id')" title="Crear Nuevo Contenedor">➕</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="estado_documento">Estado del Documento <span class="required">*</span></label>
                <select id="estado_documento" name="estado_documento" class="form-control" required>
                    <option value="DISPONIBLE" <?= ($documento['estado_documento'] ?? '') === 'DISPONIBLE' ? 'selected' : '' ?>>🟢 Disponible</option>
                    <option value="FALTA" <?= ($documento['estado_documento'] ?? '') === 'FALTA' ? 'selected' : '' ?>>🔴 Falta</option>
                    <option value="PRESTADO" <?= ($documento['estado_documento'] ?? '') === 'PRESTADO' ? 'selected' : '' ?>>🔵 Prestado</option>
                    <option value="NO UTILIZADO" <?= ($documento['estado_documento'] ?? '') === 'NO UTILIZADO' ? 'selected' : '' ?>>🟡 No Utilizado</option>
                    <option value="ANULADO" <?= ($documento['estado_documento'] ?? '') === 'ANULADO' ? 'selected' : '' ?>>🟣 Anulado</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="4"><?= htmlspecialchars($documento['observaciones'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            <a href="/catalogacion/ver/<?= $documento['id'] ?>" class="btn btn-secondary">❌ Cancelar</a>
            <button type="button" class="btn btn-danger" onclick="confirmarEliminacion()">🗑️ Eliminar Documento</button>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.required {
    color: #E53E3E;
    font-weight: bold;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #E2E8F0;
}

.document-form {
    padding: 20px;
}
</style>

<script>
function confirmarEliminacion() {
    if (confirm('¿Está seguro que desea eliminar este documento?\n\nEsta acción no se puede deshacer.')) {
        window.location.href = '/catalogacion/eliminar/<?= $documento['id'] ?>';
    }
}

function filtrarContenedores() {
    const ubicacionId = document.getElementById('filtro_ubicacion').value;
    const selectContenedor = document.getElementById('contenedor_fisico_id');
    const options = selectContenedor.options;
    
    // Guardar selección actual
    const currentVal = selectContenedor.value;
    let foundCurrent = false;

    for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        if (opt.value === "") continue; // "Sin asignar" siempre visible
        
        const optUbicacion = opt.getAttribute('data-ubicacion');
        
        if (ubicacionId === "" || optUbicacion == ubicacionId) {
            opt.style.display = "";
            if (opt.value == currentVal) foundCurrent = true;
        } else {
            opt.style.display = "none";
        }
    }
    
    // Si la selección actual quedó oculta (porque cambiamos de ubicación), resetear a "Sin asignar"
    // Pero solo si no estamos viendo "Todas"
    if (ubicacionId !== "" && !foundCurrent && currentVal !== "") {
        selectContenedor.value = "";
    }
}

// Inicializar filtro si el documento ya tiene contenedor con ubicación
document.addEventListener('DOMContentLoaded', function() {
    const selectContenedor = document.getElementById('contenedor_fisico_id');
    const selectedOption = selectContenedor.options[selectContenedor.selectedIndex];
    
    if (selectedOption && selectedOption.getAttribute('data-ubicacion')) {
        const ubicacionId = selectedOption.getAttribute('data-ubicacion');
        const filtroUbicacion = document.getElementById('filtro_ubicacion');
        if (filtroUbicacion) {
            filtroUbicacion.value = ubicacionId;
            filtrarContenedores(); // Aplicar filtro visualmente
        }
    }
});
</script>

<?php 
// Include Modal Partial
require __DIR__ . '/../layouts/modal_crear_contenedor.php';

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
