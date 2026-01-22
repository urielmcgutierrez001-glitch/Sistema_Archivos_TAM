<?php 
ob_start(); 
$pageTitle = 'Crear Nuevo Documento';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📝 Crear Nuevo Documento</h2>
        <a href="/catalogacion" class="btn btn-secondary">← Volver al Listado</a>
    </div>
    
    <form method="POST" action="/catalogacion/guardar" class="document-form" id="createForm">
        
        <!-- Toggle Mode -->
        <div class="form-group" style="margin-bottom: 20px; background: #f8f9fa; padding: 10px; border-radius: 5px;">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="modo_lote" name="modo_lote" value="1" onchange="toggleMode()">
                <label class="custom-control-label" for="modo_lote"><strong>Activar Creación por Lote (Múltiples Documentos)</strong></label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento <span class="required">*</span></label>
                <select id="tipo_documento" name="tipo_documento" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php if (isset($tiposDocumento)): ?>
                        <?php foreach ($tiposDocumento as $td): ?>
                            <option value="<?= $td['codigo'] ?>">
                                <?= htmlspecialchars($td['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión <span class="required">*</span></label>
                <input type="number" id="gestion" name="gestion" class="form-control" 
                       value="<?= date('Y') ?>" min="2000" max="<?= date('Y') + 1 ?>" required>
            </div>
        </div>
        
        <!-- Single Mode Input -->
        <div id="single-mode">
            <div class="form-row">
                <div class="form-group">
                    <label for="nro_comprobante">Número de Comprobante <span class="required">*</span></label>
                    <input type="text" id="nro_comprobante" name="nro_comprobante" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="codigo_abc">Código ABC</label>
                    <input type="text" id="codigo_abc" name="codigo_abc" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="estado_documento">Estado del Documento <span class="required">*</span></label>
                <select id="estado_documento" name="estado_documento" class="form-control">
                    <option value="DISPONIBLE" selected>🟢 Disponible</option>
                    <option value="FALTA">🔴 Falta</option>
                    <option value="PRESTADO">🔵 Prestado</option>
                    <option value="NO UTILIZADO">🟡 No Utilizado</option>
                    <option value="ANULADO">🟣 Anulado</option>
                </select>
            </div>
        </div>

        <!-- Batch Mode Inputs -->
        <div id="batch-mode" style="display: none;">
            <div class="form-row">
                <div class="form-group">
                    <label for="nro_desde">Desde Nro. <span class="required">*</span></label>
                    <input type="number" id="nro_desde" name="nro_desde" class="form-control" placeholder="Ej: 1">
                </div>
                <div class="form-group">
                    <label for="nro_hasta">Hasta Nro. <span class="required">*</span></label>
                    <input type="number" id="nro_hasta" name="nro_hasta" class="form-control" placeholder="Ej: 50">
                </div>
            </div>
            
             <div class="form-group">
                <label for="codigo_abc_batch">Código ABC (Opcional, se aplicará a todos)</label>
                <input type="text" id="codigo_abc_batch" name="codigo_abc" class="form-control" disabled> <!-- Disabled initially, logic relies on same name if single form submit, but here we might duplicate name? JS will handle enabling/disabling -->
            </div>

            <button type="button" class="btn btn-purple btn-block" onclick="generarLista()" style="background-color: #6f42c1; color: white;">Generar Lista de Documentos</button>
            
            <!-- List Container -->
            <div id="batch-list-container" style="margin-top: 20px; max-height: 500px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                <div class="text-center text-muted p-3">Defina el rango y presione Generar</div>
            </div>
        </div>
        
        <div class="form-row" style="margin-top: 20px;">
            <div class="form-group">
                <label for="contenedor_fisico_id">Contenedor Físico (Libro/Amarro) <small class="text-muted">(Se aplicará a todos)</small></label>
                <div style="display:flex; gap:5px;">
                    <select id="contenedor_fisico_id" name="contenedor_fisico_id" class="form-control">
                        <option value="">Sin asignar</option>
                        <?php foreach ($contenedores as $cont): ?>
                            <option value="<?= $cont['id'] ?>">
                                <?= htmlspecialchars($cont['tipo_documento_abreviatura'] ?? $cont['tipo_documento_codigo'] ?? 'DOC') ?> <?= htmlspecialchars($cont['gestion']) ?> <?= htmlspecialchars($cont['tipo_contenedor']) ?> #<?= htmlspecialchars($cont['numero']) ?>
                                <?php if (!empty($cont['codigo_abc'])): ?>
                                    (<?= htmlspecialchars($cont['codigo_abc']) ?>)
                                <?php endif; ?>
                                <?php if (!empty($cont['ubicacion_nombre'])): ?>
                                    - <?= htmlspecialchars($cont['ubicacion_nombre']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-success" onclick="abrirModalCrearContenedor('contenedor_fisico_id')" title="Crear Nuevo Contenedor">➕</button>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="4"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="btn-save">💾 Guardar Documento(s)</button>
            <a href="/catalogacion" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>

<script>
function toggleMode() {
    const isBatch = document.getElementById('modo_lote').checked;
    const singleMode = document.getElementById('single-mode');
    const batchMode = document.getElementById('batch-mode');
    const btnSave = document.getElementById('btn-save');

    if (isBatch) {
        singleMode.style.display = 'none';
        batchMode.style.display = 'block';
        
        // Disable single inputs to avoid required validation, Enable batch inputs
        document.getElementById('nro_comprobante').required = false;
        document.getElementById('nro_desde').required = true;
        document.getElementById('nro_hasta').required = true;
        
        // Handle name collision or just disable single
        document.getElementById('nro_comprobante').disabled = true;
        document.getElementById('codigo_abc').disabled = true; 
        document.getElementById('codigo_abc_batch').disabled = false; // Use batch input
        
        btnSave.innerHTML = '💾 Guardar Lote';
    } else {
        singleMode.style.display = 'block';
        batchMode.style.display = 'none';
        
        document.getElementById('nro_comprobante').required = true;
        document.getElementById('nro_desde').required = false;
        document.getElementById('nro_hasta').required = false;
        
        document.getElementById('nro_comprobante').disabled = false;
        document.getElementById('codigo_abc').disabled = false;
        document.getElementById('codigo_abc_batch').disabled = true;

        btnSave.innerHTML = '💾 Guardar Documento';
    }
}

function generarLista() {
    const listContainer = document.getElementById('batch-list-container');
    const desde = parseInt(document.getElementById('nro_desde').value);
    const hasta = parseInt(document.getElementById('nro_hasta').value);
    
    if (isNaN(desde) || isNaN(hasta) || desde > hasta) {
        alert('Por favor ingrese un rango válido (Desde debe ser menor o igual a Hasta)');
        return;
    }
    
    // Limit removed per user request
    /* if ((hasta - desde) > 100) {
        alert('El rango no puede exceder 100 documentos por seguridad.');
        return;
    } */

    listContainer.innerHTML = '';
    
    for (let i = desde; i <= hasta; i++) {
        const item = document.createElement('div');
        item.className = 'batch-item';
        // Using radio buttons with same name per item to ensure single selection for status
        // But requested UI is checkboxes. I'll make them behave like radios or just standard mapping.
        // Requested: Checked "Existe" default. "Anulado", "No utilizado".
        
        item.innerHTML = `
            <div style="font-weight: bold; margin-bottom: 5px;">Documento ${i}</div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="batch_existe[${i}]" value="1" checked> 
                    ✅ Existe físicamente
                </label>
                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="batch_anulado[${i}]" value="1"> 
                    🚫 Anulado
                </label>
                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="batch_no_util[${i}]" value="1"> 
                    ⚪ No utilizado
                </label>
            </div>
        `;
        listContainer.appendChild(item);
    }
}

function updateRowColor(radio) {
    // Optional: Visual feedback
}
</script>

<style>
.batch-item {
    background: white;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
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

<?php 
// Include Modal Partial
require __DIR__ . '/../layouts/modal_crear_contenedor.php';

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
