<?php 
ob_start(); 
$pageTitle = 'Gestión de Préstamos';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📤 Gestión de Préstamos</h2>
        <a href="/prestamos/crear" class="btn btn-primary">➕ Nuevo Préstamo</a>
    </div>
    
    <!-- Filtros -->
    <form method="GET" class="search-form" style="padding: 20px; border-bottom: 1px solid #E2E8F0;">
        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="Prestado" <?= $filtros['estado'] === 'Prestado' ? 'selected' : '' ?>>📤 Prestado</option>
                    <option value="Devuelto" <?= $filtros['estado'] === 'Devuelto' ? 'selected' : '' ?>>✅ Devuelto</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="usuario_id">Usuario</label>
                <select id="usuario_id" name="usuario_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $usr): ?>
                        <option value="<?= $usr['id'] ?>" <?= $filtros['usuario_id'] == $usr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usr['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="margin-right: 10px;">🔍 Buscar</button>
                <a href="/prestamos" class="btn btn-secondary">🔄 Limpiar</a>
            </div>
        </div>
    </form>
    
    <!-- Tabla de préstamos -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Unidad/Área</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                    <th>Docs</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay préstamos registrados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $pres): 
                        // Verificar si está vencido
                        $vencido = ($pres['estado'] === 'Prestado' && strtotime($pres['fecha_devolucion_esperada']) < time());
                        $rowClass = $vencido ? 'row-vencido' : '';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <strong><?= htmlspecialchars($pres['unidad_nombre'] ?? 'N/A') ?></strong><br>
                                <small class="text-muted">Prestatario: <?= htmlspecialchars($pres['nombre_prestatario'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($pres['fecha_prestamo'])) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($pres['fecha_devolucion_esperada'])) ?>
                                <?php if ($vencido): ?>
                                    <br><span class="badge badge-falta">⚠️ Vencido</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info" style="background:#3182ce; color:white;">
                                    <?= $pres['total_documentos'] ?> docs
                                </span>
                            </td>
                            <td>
                                <?php if ($pres['estado'] === 'Prestado'): ?>
                                    <span class="badge badge-prestado">📤 Prestado</span>
                                <?php else: ?>
                                    <span class="badge badge-disponible">✅ Devuelto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/prestamos/ver/<?= $pres['id'] ?>" class="btn btn-sm btn-primary" title="Ver Detalle">
                                        👁️ Ver
                                    </a>
                                    <a href="/prestamos/exportar-pdf/<?= $pres['id'] ?>" target="_blank" class="btn btn-sm btn-warning" title="PDF">
                                        📄 PDF
                                    </a>
                                    <a href="/prestamos/exportar-excel/<?= $pres['id'] ?>" target="_blank" class="btn btn-sm btn-success" title="Excel">
                                        📊 Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.row-vencido {
    background-color: #fff5f5;
}

.row-vencido td {
    border-left: 3px solid #E53E3E;
}
</style>

<script>
function confirmarDevolucion(id) {
    if (confirm('¿Confirmar la devolución de este documento?\n\nSe actualizará el estado del documento a DISPONIBLE.')) {
        window.location.href = '/prestamos/devolver/' + id;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
