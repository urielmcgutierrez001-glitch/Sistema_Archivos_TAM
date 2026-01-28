<?php 
ob_start(); 
$pageTitle = 'Gestión de Préstamos';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📤 Gestión de Préstamos</h2>
        <div class="header-actions">
            <a href="/prestamos/importar" class="btn btn-secondary">📊 Importar Excel</a>
            <a href="/prestamos/crear" class="btn btn-primary">➕ Nuevo Préstamo</a>
        </div>
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
                    <th style="width: 25%;">Unidad/Área</th>
                    <th class="text-center" style="width: 15%;">Fecha Préstamo</th>
                    <th class="text-center" style="width: 15%;">Fecha Devolución</th>
                    <th class="text-center" style="width: 10%;">Docs</th>
                    <th class="text-center" style="width: 15%;">Estado</th>
                    <th class="text-center" style="width: 20%;">Acciones</th>
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
                            <td class="align-middle">
                                <div class="font-weight-bold" style="font-size: 1.05em; color: #2d3748;">
                                    <?= htmlspecialchars($pres['unidad_nombre'] ?? 'N/A') ?>
                                </div>
                                <div class="text-muted small">
                                    <i class="icon-user"></i> Prestatario: <?= htmlspecialchars($pres['nombre_prestatario'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td class="text-center align-middle" style="color: #4a5568;">
                                <?= date('d/m/Y', strtotime($pres['fecha_prestamo'])) ?>
                            </td>
                            <td class="text-center align-middle" style="color: #4a5568;">
                                <?= date('d/m/Y', strtotime($pres['fecha_devolucion_esperada'])) ?>
                                <?php if ($vencido): ?>
                                    <br><span class="badge badge-falta" style="font-size: 0.75em;">⚠️ Vencido</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <div style="line-height: 1.2; color: #4a5568;">
                                    <span style="font-size: 1.2em; font-weight: bold; display: block;"><?= $pres['total_documentos'] ?></span>
                                    <span style="font-size: 0.85em;">docs</span>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <?php if ($pres['estado'] === 'En Proceso'): ?>
                                    <span class="badge badge-warning" style="font-weight: 500; letter-spacing: 0.5px;">⚠️ Por Procesar</span>
                                <?php elseif ($pres['estado'] === 'Prestado'): ?>
                                    <span class="badge badge-prestado" style="font-weight: 500; letter-spacing: 0.5px;">📥 Prestado</span>
                                <?php else: ?>
                                    <span class="badge badge-disponible" style="font-weight: 500; letter-spacing: 0.5px;">✅ Devuelto</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <a href="/prestamos/ver/<?= $pres['id'] ?>" class="btn btn-sm btn-primary" title="Ver Detalle">
                                        Ver
                                    </a>
                                    
                                    <?php if ($pres['estado'] == 'En Proceso' || $pres['estado'] == 'Prestado'): ?>
                                        <a href="/prestamos/procesar/<?= $pres['id'] ?>" class="btn btn-sm btn-edit-custom" title="Editar">
                                            ✏️ Editar
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-edit-custom" disabled style="opacity: 0.5; cursor: not-allowed;" title="Ya procesado">
                                            ✏️ Editar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="confirmarEliminacion(<?= $pres['id'] ?>)" class="btn btn-sm btn-danger" title="Eliminar">
                                        🗑️
                                    </button>
                                    
                                    <a href="/prestamos/exportar-pdf/<?= $pres['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="PDF">
                                        📄 PDF
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

</style>

<style>
/* Custom Button Styles for Prestamos */
.btn-edit-custom {
    background-color: #6c757d; /* Plomo/Gris default */
    border-color: #6c757d;
    color: white;
    transition: all 0.3s ease;
}

.btn-edit-custom:hover:not(:disabled) {
    background-color: #ffc107; /* Amarillo on hover */
    border-color: #ffc107;
    color: #212529; /* Texto oscuro */
}

/* Fix flex alignment for buttons */
.btn-sm {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 32px; /* Fixed height for consistency */
    padding: 0 10px;
    vertical-align: middle;
    line-height: normal; /* Override bootstrap */
}

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

function confirmarEliminacion(id) {
    if (confirm('¿Está seguro de eliminar este préstamo?\n\nSe eliminará el registro y los documentos se liberarán (volverán a DISPONIBLE).')) {
        window.location.href = '/prestamos/eliminar/' + id;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
