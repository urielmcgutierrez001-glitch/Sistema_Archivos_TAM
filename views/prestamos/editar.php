<?php 
ob_start(); 
$pageTitle = 'Editar Préstamo #' . $prestamo['id'];
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>✏️ Editar Préstamo #<?= $prestamo['id'] ?></h2>
        <div class="header-actions">
            <a href="/prestamos/ver/<?= $prestamo['id'] ?>" class="btn btn-secondary">← Volver al Detalle</a>
        </div>
    </div>
    
    <!-- 1. Formulario de Edición de Encabezado -->
    <div class="edit-section" style="background: #f8f9fa; padding: 20px; border-bottom: 2px solid #e2e8f0;">
        <h3 style="color: #1B3C84; margin-bottom: 15px; border-bottom: 1px solid #cbd5e0; padding-bottom: 5px;">
            📝 Datos del Préstamo
        </h3>
        
        <form action="/prestamos/actualizarEncabezado/<?= $prestamo['id'] ?>" method="POST">
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="unidad_area_id">Unidad/Área Solicitante <span class="required">*</span></label>
                    <select id="unidad_area_id" name="unidad_area_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($unidades as $ubi): ?>
                            <option value="<?= $ubi['id'] ?>" <?= $prestamo['unidad_area_id'] == $ubi['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ubi['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nombre_prestatario">Nombre Prestatario</label>
                    <input type="text" id="nombre_prestatario" name="nombre_prestatario" class="form-control" 
                           value="<?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_prestamo">Fecha de Préstamo <span class="required">*</span></label>
                    <input type="date" id="fecha_prestamo" name="fecha_prestamo" class="form-control" 
                           value="<?= $prestamo['fecha_prestamo'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_devolucion_esperada">Fecha Devolución Est.</label>
                    <input type="date" id="fecha_devolucion_esperada" name="fecha_devolucion_esperada" class="form-control" 
                           value="<?= $prestamo['fecha_devolucion_esperada'] ?>">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="observaciones">Observaciones</label>
                    <input type="text" id="observaciones" name="observaciones" class="form-control" 
                           value="<?= htmlspecialchars($prestamo['observaciones'] ?? '') ?>">
                </div>
            </div>
            
            <div style="margin-top: 15px; text-align: right;">
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios del Encabezado</button>
            </div>
        </form>
    </div>

    <!-- 2. Lista de Documentos Actuales -->
    <div class="edit-section" style="padding: 20px;">
        <h3 style="color: #1B3C84; margin-bottom: 15px; border-bottom: 1px solid #cbd5e0; padding-bottom: 5px; display: flex; justify-content: space-between;">
            <span>📚 Documentos en este Préstamo</span>
            <span class="badge badge-info"><?= count($detalles) ?> Docs</span>
        </h3>
        
        <?php if (empty($detalles)): ?>
            <div class="alert alert-warning">No hay documentos en este préstamo. Utilice el buscador abajo para agregar.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Contenedor</th>
                            <th>Ubicación</th>
                            <th>Estado Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $doc): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong><br>
                                    <small><?= htmlspecialchars($doc['gestion']) ?> | #<?= htmlspecialchars($doc['nro_comprobante']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($doc['tipo_contenedor'] ?? '') ?> #<?= htmlspecialchars($doc['contenedor_numero'] ?? '') ?></td>
                                <td><small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></small></td>
                                <td>
                                    <?php if ($doc['estado'] === 'Prestado'): ?>
                                        <span class="badge badge-prestado">Prestado</span>
                                    <?php elseif ($doc['estado'] === 'Devuelto'): ?>
                                        <span class="badge badge-disponible">Devuelto</span>
                                    <?php else: ?>
                                        <span class="badge"><?= $doc['estado'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/prestamos/quitarDetalle/<?= $doc['id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('¿Quitar este documento del préstamo? Volverá a estar DISPONIBLE.');">
                                        ✕ Quitar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Buscador para Agregar Nuevos Documentos -->
    <div class="edit-section search-section" style="background: #f0f4f8; padding: 20px; border-top: 2px solid #e2e8f0;">
        <h3 style="color: #2c5282; margin-bottom: 15px;">🔍 Buscar y Agregar Documentos</h3>
        
        <form method="GET" action="/prestamos/editar/<?= $prestamo['id'] ?>" class="search-form">
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($filtros['search']) ?>">
                </div>
                <div class="form-group">
                    <input type="number" name="gestion" class="form-control" placeholder="Gestión" value="<?= htmlspecialchars($filtros['gestion']) ?>">
                </div>
                <div class="form-group">
                    <select name="tipo_documento" class="form-control">
                        <option value="">-- Tipo --</option>
                        <?php foreach ($tiposDocumento as $td): ?>
                            <option value="<?= $td['codigo'] ?>" <?= $filtros['tipo_documento'] == $td['codigo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($td['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Buscar</button>
                    <a href="/prestamos/editar/<?= $prestamo['id'] ?>" class="btn btn-secondary btn-block">Limpiar</a>
                </div>
            </div>
            
            <!-- Preserve pagination params if needed, mostly reset on new search -->
        </form>

        <div class="table-responsive" style="margin-top: 15px; background: white; padding: 10px; border-radius: 4px;">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Gestión</th>
                        <th>Nro</th>
                        <th>Contenedor</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentosDisponibles)): ?>
                        <tr><td colspan="6" class="text-center">No se encontraron documentos disponibles con los filtros actuales.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documentosDisponibles as $doc): ?>
                            <tr>
                                <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                                <td><?= htmlspecialchars($doc['gestion']) ?></td>
                                <td><?= htmlspecialchars($doc['nro_comprobante']) ?></td>
                                <td><?= htmlspecialchars($doc['tipo_contenedor'] ?? '') ?> #<?= htmlspecialchars($doc['contenedor_numero'] ?? '') ?></td>
                                <td>
                                    <?php
                                        $est = $doc['estado_documento'];
                                        $badge = 'badge-secondary';
                                        if ($est === 'DISPONIBLE') $badge = 'badge-disponible';
                                        if ($est === 'FALTA') $badge = 'badge-falta';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $est ?></span>
                                </td>
                                <td>
                                    <form action="/prestamos/agregarDetalle" method="POST" style="margin:0;">
                                        <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">
                                        <input type="hidden" name="documento_id" value="<?= $doc['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">➕ Agregar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Paginación Simple -->
            <?php if ($paginacion['total_pages'] > 1): ?>
                <div class="pagination" style="justify-content: center; gap: 5px; margin-top: 10px;">
                    <?php for ($i = 1; $i <= $paginacion['total_pages']; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($filtros, ['page' => $i])) ?>" 
                           class="btn btn-sm <?= $i == $paginacion['page'] ? 'btn-primary' : 'btn-light' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.flex-between {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.required { color: red; }
.badge-disponible { background-color: #28a745; color: white; }
.badge-prestado { background-color: #fd7e14; color: white; }
.badge-falta { background-color: #dc3545; color: white; }
.table-sm td, .table-sm th { padding: 0.3rem; }
</style>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
