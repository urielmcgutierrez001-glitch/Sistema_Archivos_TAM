
<!-- Modal de Creación Rápida de Contenedor -->
<div id="modalCrearContenedor" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:white; padding:25px; border-radius:8px; width:500px; max-width:90%; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
            <h3 style="margin:0; color:#1B3C84;">➕ Nuevo Contenedor</h3>
            <button type="button" onclick="cerrarModalCrearContenedor()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <form id="formCrearContenedorRapido">
            <!-- Hidden input to store who called us -->
            <input type="hidden" id="targetSelectId" value="">

            <!-- New Field: Tipo Documento -->
            <div class="form-group" style="margin-bottom:15px;">
                <label>Tipo de Documento (Para etiqueta DIA/RI/etc) <span style="color:red">*</span></label>
                <select name="tipo_documento" id="quick_tipo_documento" class="form-control" required style="width:100%; padding:8px;">
                    <option value="">Seleccione...</option>
                    <?php if(isset($tiposDocumento)): ?>
                        <?php foreach($tiposDocumento as $td): ?>
                            <option value="<?= $td['id'] ?>"><?= htmlspecialchars($td['nombre']) ?> (<?= htmlspecialchars($td['codigo']) ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Tipo de Contenedor <span style="color:red">*</span></label>
                <select name="tipo_contenedor" class="form-control" required style="width:100%; padding:8px;">
                    <option value="AMARRO">Amarro</option>
                    <option value="LIBRO">Libro</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Número <span style="color:red">*</span></label>
                <input type="number" name="numero" class="form-control" required placeholder="Ej: 1" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>

            <!-- New Field: Codigo ABC -->
            <div class="form-group" style="margin-bottom:15px;">
                <label>Código ABC</label>
                <input type="text" name="codigo_abc" class="form-control" placeholder="Opcional" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Gestión (Año)</label>
                <input type="number" name="gestion" class="form-control" value="<?= date('Y') ?>" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>
            
            <div class="form-group" style="margin-bottom:20px;">
                <label>Ubicación Física</label>
                <!-- We need to fetch locations or pass them. For simplify, we'll try to clone from parent or just use input -->
                <!-- Ideally, this partial should receive $ubicaciones. If not available, we show a simplified input or fetch -->
                <select name="ubicacion_id" id="quick_ubicacion_id" class="form-control" style="width:100%; padding:8px;">
                    <option value="">-- Sin asignar --</option>
                    <?php if(isset($ubicaciones)): ?>
                        <?php foreach($ubicaciones as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearContenedor()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarContenedorRapido()">💾 Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
let targetSelectElementId = null;

function abrirModalCrearContenedor(targetId) {
    targetSelectElementId = targetId;
    document.getElementById('targetSelectId').value = targetId;
    document.getElementById('modalCrearContenedor').style.display = 'flex';
    
    // Attempt to populate locations if empty (optional enhancement)
    // For now assuming $ubicaciones is passed to the view including this partial
}

function cerrarModalCrearContenedor() {
    document.getElementById('modalCrearContenedor').style.display = 'none';
    document.getElementById('formCrearContenedorRapido').reset();
}

function guardarContenedorRapido() {
    const form = document.getElementById('formCrearContenedorRapido');
    if (!form.reportValidity()) return;

    const data = {
        tipo_documento: form.tipo_documento.value,
        tipo_contenedor: form.tipo_contenedor.value,
        numero: form.numero.value,
        codigo_abc: form.codigo_abc.value,
        gestion: form.gestion.value,
        ubicacion_id: form.ubicacion_id.value
    };

    // Disable button
    const btn = form.querySelector('.btn-primary');
    const originalText = btn.innerText;
    btn.innerText = 'Guardando...';
    btn.disabled = true;

    fetch('/contenedores/guardar-rapido', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Add option to the target select
            if (targetSelectElementId) {
                const selectInfo = targetSelectElementId.split('|'); // Handle multiple if needed, but simple ID is enough usually
                // Support multiple selects? Just one for now.
                
                const select = document.getElementById(targetSelectElementId);
                if (select) {
                    const option = new Option(result.data.text, result.data.id);
                    // Add attributes if needed
                    option.setAttribute('data-ubicacion', result.data.ubicacion_id || '');
                    
                    select.add(option, select.options[1]); // Add top after "Sin asignar"
                    select.value = result.data.id;
                    
                    // Trigger change event if needed
                    const event = new Event('change');
                    select.dispatchEvent(event);
                }
            }
            cerrarModalCrearContenedor();
            // Optional: Show toast success
            alert('Contenedor creado exitosamente');
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
}
</script>
