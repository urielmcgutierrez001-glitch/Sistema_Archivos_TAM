<?php 
ob_start(); 
$pageTitle = 'Editar Préstamo #' . $prestamo['id'];
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>✏️ Editar Préstamo #<?= $prestamo['id'] ?></h2>
        <div class="header-actions" style="display: flex; gap: 10px;">
            <a href="/prestamos" class="btn btn-secondary">Volver al Listado</a>
            <a href="/prestamos/ver/<?= $prestamo['id'] ?>" class="btn btn-primary">Ver Detalle</a>
        </div>
    </div>
    
    <!-- 1. Formulario de Edición de Encabezado -->
    <div class="edit-section" style="background: #f8f9fa; padding: 20px; border-bottom: 2px solid #e2e8f0;">
        <h3 style="color: #1B3C84; margin-bottom: 15px; border-bottom: 1px solid #cbd5e0; padding-bottom: 5px;">
            📝 Datos del Préstamo
        </h3>
        
        <form action="/prestamos/confirmarProceso" method="POST" id="form-prestamo">
            <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">
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
            
        <!-- Deleted separate header save button -->
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
                <!-- Table (Form opened above) -->
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">
                                    <input type="checkbox" id="check-all-docs" onclick="toggleAllDocs(this)" checked style="transform: scale(1.5); cursor: pointer;">
                                </th>
                                <th>Documento</th>
                                <th>Contenedor</th>
                                <th>Ubicación</th>
                                <th>Estado Documento</th>
                                <th>Estado Solicitud</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $doc): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="documentos[]" value="<?= $doc['id'] ?>" class="doc-check" checked style="transform: scale(1.5); cursor: pointer;">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong><br>
                                        <small><?= htmlspecialchars($doc['gestion']) ?> | #<?= htmlspecialchars($doc['nro_comprobante']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($doc['tipo_contenedor'] ?? '') ?> #<?= htmlspecialchars($doc['contenedor_numero'] ?? '') ?></td>
                                    <td><small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></small></td>
                                    <td>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($doc['estado_anterior'] ?? 'N/A') ?></span>
                                    </td>
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
         
        <div style="margin-top: 15px; text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg">
                    Confirmar Préstamo
            </button>
            <p class="text-muted"><small>* Se guardarán los cambios del encabezado y la confirmación de documentos.</small></p>
        </div>
        </form> <!-- Close consolidated form -->
    </div>
    
    <script>
    function toggleAllDocs(source) {
        const checkboxes = document.querySelectorAll('.doc-check');
        for(let i=0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
    </script>

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
                                <td>
                                    <?php if (!empty($doc['contenedor_numero'])): ?>
                                        <span class="badge badge-info"><?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?></span>
                                    <?php else: ?>
                                        Sin asignar
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $est = $doc['estado_documento'];
                                        $badgeClass = '';
                                        $icon = '';
                                        switch($est) {
                                            case 'DISPONIBLE':
                                                $badgeClass = 'badge-disponible';
                                                $icon = '🟢';
                                                break;
                                            case 'FALTA':
                                                $badgeClass = 'badge-falta';
                                                $icon = '🔴';
                                                break;
                                            case 'PRESTADO':
                                                $badgeClass = 'badge-prestado';
                                                $icon = '🔵';
                                                break;
                                            case 'ANULADO':
                                                $badgeClass = 'badge-anulado';
                                                $icon = '🟣';
                                                break;
                                            case 'NO UTILIZADO':
                                                $badgeClass = 'badge-no-utilizado';
                                                $icon = '🟡';
                                                break;
                                            default:
                                                $badgeClass = 'badge-secondary';
                                        }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $icon ?> <?= $est ?></span>
                                </td>
                                <td>
                                    <form action="/prestamos/agregarDetalle" method="POST" style="margin:0;">
                                        <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">
                                        <input type="hidden" name="documento_id" value="<?= $doc['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm btn-agregar">➕ Agregar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Paginación Google Style -->
            <?php if (($paginacion['total_pages'] ?? 0) > 1): ?>
                <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; padding: 15px 0; flex-wrap: wrap;">
                    <?php 
                        $current = $paginacion['page'];
                        $total_p = $paginacion['total_pages'];
                        $max_visible = 10;
                        
                        $start = max(1, $current - floor($max_visible / 2));
                        $end = min($total_p, $start + $max_visible - 1);
                        
                        if ($end - $start + 1 < $max_visible) {
                            $start = max(1, $end - $max_visible + 1);
                        }
                        
                        $params = $filtros;
                    ?>

                    <!-- Primera -->
                    <?php if ($current > 1): ?>
                        <a href="?<?= http_build_query(array_merge($params, ['page' => 1])) ?>" class="btn btn-secondary btn-sm">⇤ Primero</a>
                    <?php endif; ?>

                    <!-- Anterior -->
                    <?php if ($current > 1): ?>
                        <a href="?<?= http_build_query(array_merge($params, ['page' => $current - 1])) ?>" class="btn btn-warning btn-sm">← Anterior</a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>← Anterior</button>
                    <?php endif; ?>
                    
                    <!-- Números -->
                    <div class="pagination-numbers" style="display: flex; gap: 2px; background: #fff; padding: 3px; border-radius: 4px; border: 1px solid #dee2e6;">
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($params, ['page' => $i])) ?>" 
                               class="btn btn-sm <?= $i == $current ? 'btn-primary' : 'btn-light' ?>" style="border-radius: 2px;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Siguiente -->
                    <?php if ($current < $total_p): ?>
                        <a href="?<?= http_build_query(array_merge($params, ['page' => $current + 1])) ?>" class="btn btn-warning btn-sm">Siguiente →</a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>Siguiente →</button>
                    <?php endif; ?>

                    <!-- Última -->
                    <?php if ($current < $total_p): ?>
                        <a href="?<?= http_build_query(array_merge($params, ['page' => $total_p])) ?>" class="btn btn-secondary btn-sm">Último ⇥</a>
                    <?php endif; ?>
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
.badge { background: #1B3C84; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-info { background: #17a2b8; }
.badge-disponible { background: #28a745; } /* Verde */
.badge-falta { background: #dc3545; } /* Rojo */
.badge-prestado { background: #17a2b8; } /* Celeste */
.badge-no-utilizado { background: #ffc107; color: #333; } /* Amarillo */
.badge-anulado { background: #6f42c1; } /* Morado */
.table-sm td, .table-sm th { padding: 0.3rem; }

/* Botón Agregar Hover Amarillo */
.btn-agregar {
    transition: all 0.3s ease;
}
.btn-agregar:hover {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
}
</style>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
