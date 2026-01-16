<?php 
ob_start(); 
$pageTitle = 'Gestión de Contenedores';
?>

<div class="card">
    <div class="card-header">
        <h2>📦 Gestión de Contenedores (Amarros/Libros)</h2>
        <div class="header-actions">
            <a href="/catalogacion" class="btn btn-secondary">← Volver a Catalogación</a>
            <button type="button" class="btn btn-warning" id="btnUpdateLocation" disabled>📦 Cambiar Ubicación</button>
            <a href="/contenedores/crear" class="btn btn-primary">➕ Nuevo Contenedor</a>
        </div>
    </div>
    
    <div class="card-body" style="background: #f8f9fa; padding: 20px; border-bottom: 1px solid #e3e6f0;">
        <form action="/contenedores" method="GET">
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Tipo Documento</label>
                    <input type="text" name="tipo_documento" class="form-control form-control-sm" placeholder="Ej: REGISTRO..." value="<?= htmlspecialchars($filtros['tipo_documento'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Número</label>
                    <input type="number" name="numero" class="form-control form-control-sm" placeholder="Ej: 1" value="<?= htmlspecialchars($filtros['numero'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Gestión</label>
                    <input type="number" name="gestion" class="form-control form-control-sm" placeholder="Año" value="<?= htmlspecialchars($filtros['gestion'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Ubicación</label>
                    <select name="ubicacion_id" class="form-control form-control-sm">
                        <option value="">-- Todas --</option>
                        <?php foreach ($ubicaciones as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filtros['ubicacion_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Tipo Cont.</label>
                    <select name="tipo_contenedor" class="form-control form-control-sm">
                        <option value="">-- Todos --</option>
                        <option value="AMARRO" <?= ($filtros['tipo_contenedor'] ?? '') == 'AMARRO' ? 'selected' : '' ?>>📦 Amarro</option>
                        <option value="LIBRO" <?= ($filtros['tipo_contenedor'] ?? '') == 'LIBRO' ? 'selected' : '' ?>>📚 Libro</option>
                    </select>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">🔍 Buscar</button>
                    <a href="/contenedores" class="btn btn-secondary btn-sm" style="flex: 1;">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                    <th>Tipo Documento</th>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Gestión</th>
                    <th>Ubicación</th>
                    <th>Bloque/Nivel</th>
                    <th>Color</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contenedores)): ?>
                    <tr><td colspan="8" class="text-center">No hay contenedores registrados</td></tr>
                <?php else: ?>
                    <?php foreach ($contenedores as $c): ?>
                        <tr>
                             <td><input type="checkbox" class="select-item" value="<?= $c['id'] ?>"></td>
                             <td><?= htmlspecialchars($c['tipo_documento'] ?? '-') ?></td>
                             <td>
                                <?php if ($c['tipo_contenedor'] == 'LIBRO'): ?>
                                    <span class="badge" style="background: #17a2b8;">📚 Libro</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #6f42c1;">📦 Amarro</span>
                                <?php endif; ?>
                             </td>
                             <td><strong>#<?= htmlspecialchars($c['numero'] ?? '') ?></strong></td>
                             <td><?= htmlspecialchars($c['gestion'] ?? 'N/A') ?></td>
                             <td><?= htmlspecialchars($c['ubicacion_nombre'] ?? 'Sin asignar') ?></td>
                             <td><?= htmlspecialchars($c['bloque_nivel'] ?? '-') ?></td>
                             <td>
                                <?php if (!empty($c['color'])): ?>
                                    <span class="badge" style="background-color: #eee; color: <?= htmlspecialchars($c['color']) ?>; border: 1px solid #ccc;">
                                        <?= htmlspecialchars($c['color']) ?>
                                    </span>
                                <?php endif; ?>
                             </td>
                             <td>
                                <a href="/contenedores/editar/<?= $c['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                <a href="/contenedores/eliminar/<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este contenedor? Asegúrate de que no tenga documentos asociados.')">🗑️</a>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.badge { padding: 5px 10px; border-radius: 12px; color: white; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
.btn-sm { padding: 2px 8px; font-size: 12px; }
</style>

<!-- Modal Cambio Ubicación Masiva -->
<div class="modal fade" id="locationModal" tabindex="-1" role="dialog" style="display: none; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050;">
    <div class="modal-dialog" role="document" style="margin: 10% auto; max-width: 400px; background: white; border-radius: 5px; box-shadow: 0 3px 7px rgba(0,0,0,0.3);">
        <div class="modal-content">
            <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h5 class="modal-title">Cambiar Ubicación (<span id="countSelected">0</span>)</h5>
                <button type="button" class="close" onclick="document.getElementById('locationModal').style.display='none'" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form action="/contenedores/actualizar-ubicacion-masiva" method="POST" id="bulkLocationForm">
                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label>Nueva Ubicación:</label>
                        <select name="ubicacion_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($ubicaciones as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="selectedIdsInputContainer"></div>
                </div>
                <div class="modal-footer" style="padding: 15px; border-top: 1px solid #eee; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('locationModal').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.select-item');
    const btnUpdate = document.getElementById('btnUpdateLocation');
    const countSpan = document.getElementById('countSelected');
    const containerInputs = document.getElementById('selectedIdsInputContainer');
    const modal = document.getElementById('locationModal');
    
    // Toggle All
    if(selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateButtonState();
        });
    }
    
    // Toggle Individual
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateButtonState);
    });
    
    function updateButtonState() {
        const selected = document.querySelectorAll('.select-item:checked');
        if(btnUpdate) {
            btnUpdate.disabled = selected.length === 0;
            if (countSpan) countSpan.textContent = selected.length;
        }
    }
    
    // Open Modal logic
    if(btnUpdate) {
        btnUpdate.addEventListener('click', function() {
            if (this.disabled) return;
            
            containerInputs.innerHTML = '';
            const selected = document.querySelectorAll('.select-item:checked');
            
            selected.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                containerInputs.appendChild(input);
            });
            
            modal.style.display = 'block';
        });
    }
    
    // Close modal on outside click
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
