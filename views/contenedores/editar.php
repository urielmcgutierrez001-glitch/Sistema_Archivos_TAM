<?php 
ob_start(); 
$pageTitle = 'Editar Contenedor';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2>✏️ Editar Contenedor #<?= htmlspecialchars($contenedor['id']) ?></h2>
        <a href="/contenedores" class="btn btn-secondary">← Cancelar</a>
    </div>
    
    <form action="/contenedores/actualizar/<?= $contenedor['id'] ?>" method="POST" style="padding: 20px;">
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="tipo_contenedor">Tipo de Contenedor <span style="color:red">*</span></label>
                <select name="tipo_contenedor" id="tipo_contenedor" class="form-control" required>
                    <option value="AMARRO" <?= $contenedor['tipo_contenedor'] == 'AMARRO' ? 'selected' : '' ?>>Amarro</option>
                    <option value="LIBRO" <?= $contenedor['tipo_contenedor'] == 'LIBRO' ? 'selected' : '' ?>>Libro</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento (Contenido)</label>
                <input type="text" name="tipo_documento" id="tipo_documento" class="form-control" value="<?= htmlspecialchars($contenedor['tipo_documento'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="numero">Número <span style="color:red">*</span></label>
                <input type="number" name="numero" id="numero" class="form-control" required value="<?= htmlspecialchars($contenedor['numero']) ?>">
            </div>

            <div class="form-group">
                <label for="codigo_abc">Código ABC (Opcional)</label>
                <input type="text" name="codigo_abc" id="codigo_abc" class="form-control" value="<?= htmlspecialchars($contenedor['codigo_abc'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="gestion">Gestión (Año)</label>
                <input type="number" name="gestion" id="gestion" class="form-control" value="<?= htmlspecialchars($contenedor['gestion'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="color">Color (Opcional)</label>
                <select name="color" id="color" class="form-control">
                    <option value="" <?= empty($contenedor['color']) ? 'selected' : '' ?>>-- Ninguno --</option>
                    <option value="ROJO" <?= ($contenedor['color'] ?? '') == 'ROJO' ? 'selected' : '' ?>>Rojo</option>
                    <option value="AZUL" <?= ($contenedor['color'] ?? '') == 'AZUL' ? 'selected' : '' ?>>Azul</option>
                    <option value="VERDE" <?= ($contenedor['color'] ?? '') == 'VERDE' ? 'selected' : '' ?>>Verde</option>
                    <option value="AMARILLO" <?= ($contenedor['color'] ?? '') == 'AMARILLO' ? 'selected' : '' ?>>Amarillo</option>
                    <option value="NEGRO" <?= ($contenedor['color'] ?? '') == 'NEGRO' ? 'selected' : '' ?>>Negro</option>
                    <option value="BLANCO" <?= ($contenedor['color'] ?? '') == 'BLANCO' ? 'selected' : '' ?>>Blanco</option>
                </select>
            </div>
        </div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="ubicacion_id">Ubicación Física</label>
                <select name="ubicacion_id" id="ubicacion_id" class="form-control">
                    <option value="">-- Sin asignar --</option>
                    <?php foreach ($ubicaciones as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $u['id'] == $contenedor['ubicacion_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bloque_nivel">Bloque / Nivel</label>
                <input type="text" name="bloque_nivel" id="bloque_nivel" class="form-control" value="<?= htmlspecialchars($contenedor['bloque_nivel'] ?? '') ?>">
            </div>
        </div>

        <!-- Sección de Documentos Contenidos -->
        <div class="card" style="margin-bottom: 20px; border: 1px solid #e3e6f0;">
            <div class="card-header" style="background: #f8f9fa;">
                <h5 style="margin: 0; font-size: 16px;">📂 Documentos en este contenedor</h5>
                <small class="text-muted">Desmarca los documentos que desees quitar de este contenedor al guardar.</small>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover" style="margin: 0;">
                        <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                            <tr>
                                <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllDocs" checked></th>
                                <th>Tipo</th>
                                <th>No. Comprobante</th>
                                <th>Gestión</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documentos)): ?>
                                <tr><td colspan="5" class="text-center p-3">No hay documentos en este contenedor.</td></tr>
                            <?php else: ?>
                                <?php foreach ($documentos as $doc): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="documentos_ids[]" value="<?= $doc['id'] ?>" checked class="doc-checkbox">
                                        </td>
                                        <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                                        <td><strong><?= htmlspecialchars($doc['nro_comprobante']) ?></strong></td>
                                        <td><?= htmlspecialchars($doc['gestion']) ?></td>
                                        <td><small><?= htmlspecialchars($doc['observaciones'] ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('selectAllDocs').addEventListener('change', function() {
            document.querySelectorAll('.doc-checkbox').forEach(cb => cb.checked = this.checked);
        });
        </script>
        
        <div class="form-actions" style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
