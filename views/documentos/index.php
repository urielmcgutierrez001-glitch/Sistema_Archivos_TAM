<?php 
ob_start(); 
$pageTitle = 'Catalogación y Búsqueda de Documentos';
$modoLotes = isset($_GET['modo_lotes']) && $_GET['modo_lotes'] == '1';
?>

<div class="card">
    <div class="card-header">
        <h2>Búsqueda Avanzada</h2>
        <div class="header-actions">
            <a href="/catalogacion/crear" class="btn btn-primary">➕ Nuevo Documento</a>
            <?php if ($modoLotes): ?>
                <a href="/catalogacion" class="btn btn-secondary">← Modo Normal</a>
                <button type="button" class="btn btn-warning" onclick="abrirModalEdicionLote()">✏️ Editar Lote</button>
                <button type="button" class="btn btn-success" onclick="procesarLote()">📋 Generar Reporte Lote</button>
            <?php else: ?>
                <a href="/catalogacion?modo_lotes=1" class="btn btn-warning">📦 Buscar por Lotes</a>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="GET" action="/catalogacion" class="search-form">
        <?php if ($modoLotes): ?>
            <input type="hidden" name="modo_lotes" value="1">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="search">Búsqueda General</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    class="form-control" 
                    placeholder="Nro (ej. 1-50), Código ABC, Observaciones..."
                    value="<?= htmlspecialchars($filtros['search']) ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión</label>
                <input 
                    type="text" 
                    id="gestion" 
                    name="gestion" 
                    class="form-control"
                    placeholder="2023"
                    value="<?= htmlspecialchars($filtros['gestion']) ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="ubicacion_id">Ubicación</label>
                <select id="ubicacion_id" name="ubicacion_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($ubicaciones as $ub): ?>
                        <option value="<?= $ub['id'] ?>" <?= $filtros['ubicacion_id'] == $ub['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ub['nombre'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="estado_documento">Estado</label>
                <select id="estado_documento" name="estado_documento" class="form-control">
                    <option value="">Todos</option>
                    <option value="DISPONIBLE" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'DISPONIBLE' ? 'selected' : '' ?>>🟢 Disponible</option>
                    <option value="FALTA" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'FALTA' ? 'selected' : '' ?>>🔴 Falta</option>
                    <option value="PRESTADO" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'PRESTADO' ? 'selected' : '' ?>>🔵 Prestado</option>
                    <option value="NO UTILIZADO" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'NO UTILIZADO' ? 'selected' : '' ?>>🟡 No Utilizado</option>
                    <option value="ANULADO" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'ANULADO' ? 'selected' : '' ?>>🟣 Anulado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" class="form-control">
                    <option value="">-- Todos --</option>
                    <?php foreach ($tiposDocumento as $td): ?>
                        <option value="<?= $td['codigo'] ?>" <?= ($filtros['tipo_documento'] ?? '') == $td['codigo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($td['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <a href="/catalogacion?clean=1<?= $modoLotes ? '&modo_lotes=1' : '' ?>" class="btn btn-secondary">🧹 Limpiar Filtros</a>
        </div>
    </form>
</div>

<?php if ($modoLotes): ?>
<div class="alert alert-info">
    <strong>Modo Lotes Activado:</strong> Selecciona los documentos que deseas incluir en el reporte haciendo clic en los checkboxes.
</div>
<?php endif; ?>

<div class="card mt-20">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Resultados de Búsqueda</h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span>Cantidad de Filas:</span>
            <input type="number" id="perPageInput" value="<?= $paginacion['per_page'] ?? 20 ?>" min="1" max="200" 
                   style="width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc;"
                   onchange="updatePerPage(this.value)" onkeypress="if(event.key === 'Enter') updatePerPage(this.value)">
            <span class="badge"><?= number_format($paginacion['total']) ?> documentos</span>
        </div>
    </div>
    
    <script>
    function updatePerPage(val) {
        val = parseInt(val);
        if (val < 1) val = 1;
        if (val > 200) val = 200;
        
        // Construct new URL properly maintaining existing params
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('per_page', val);
        urlParams.set('page', 1); // Reset to page 1
        window.location.search = urlParams.toString();
    }
    </script>
    
    <?php if (empty($documentos)): ?>
        <div class="alert alert-info">
            No se encontraron documentos con los criterios de búsqueda especificados.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <?php if ($modoLotes): ?>
                            <th style="width: 50px;">
                                <input type="checkbox" id="seleccionar-todos" onclick="toggleTodos(this)">
                            </th>
                        <?php endif; ?>
                        
                        <?php
                        // Helper para generar links de ordenamiento
                        $currentSort = $_GET['sort'] ?? '';
                        $currentOrder = $_GET['order'] ?? '';
                        
                        $makeSortLink = function($col, $label) use ($filtros, $currentSort, $currentOrder) {
                            $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                            $icon = '';
                            if ($currentSort === $col) {
                                $icon = $currentOrder === 'ASC' ? ' ▲' : ' ▼';
                            } else {
                                $icon = ' <span style="opacity:0.3; font-size: 0.8em">⇅</span>'; // Indicador sutil
                            }
                            
                            $params = array_merge($filtros, ['sort' => $col, 'order' => $newOrder, 'page' => 1]); 
                            // Reset page to 1 when sorting
                            
                            return '<a href="?' . http_build_query($params) . '" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">' . $label . $icon . '</a>';
                        };
                        ?>
                        
                        <th><?= $makeSortLink('gestion', 'Gestión') ?></th>
                        <th><?= $makeSortLink('tipo', 'Tipo') ?></th>
                        <th><?= $makeSortLink('nro_comprobante', 'Nro Comprobante') ?></th>
                        <th><?= $makeSortLink('codigo_abc', 'Código ABC') ?></th>
                        <th><?= $makeSortLink('contenedor', 'Contenedor') ?></th>
                        <th><?= $makeSortLink('ubicacion', 'Ubicación') ?></th>
                        <th><?= $makeSortLink('estado', 'Estado') ?></th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): 
                        $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                        $tipo = $doc['tipo_documento'] ?? '';
                        
                        // Abbreviate Type
                        $abrev = 'N/A';
                        switch($tipo) {
                            case 'REGISTRO_DIARIO': $abrev = 'DIA'; break;
                            case 'REGISTRO_INGRESO': $abrev = 'ING'; break;
                            case 'REGISTRO_CEPS': $abrev = 'CEPS'; break;
                            case 'PREVENTIVOS': $abrev = 'PRE'; break;
                            case 'ASIENTOS_MANUALES': $abrev = 'MAN'; break;
                            case 'DIARIOS_APERTURA': $abrev = 'DAP'; break;
                            case 'REGISTRO_TRASPASO': $abrev = 'TRA'; break;
                            case 'HOJA_RUTA_DIARIOS': $abrev = 'HRD'; break;
                            default: $abrev = substr($tipo, 0, 3); 
                        }

                        $rowClass = '';
                        switch($estado) {
                            case 'FALTA':
                                $rowClass = 'row-falta';
                                break;
                            case 'PRESTADO':
                                $rowClass = 'row-prestado';
                                break;
                            case 'ANULADO':
                                $rowClass = 'row-anulado';
                                break;
                            case 'NO UTILIZADO':
                                $rowClass = 'row-no-utilizado';
                                break;
                            case 'DISPONIBLE':
                                $rowClass = 'row-disponible';
                                break;
                        }
                    ?>
                        <tr class="<?= $rowClass ?>" data-doc-id="<?= $doc['id'] ?>">
                            <?php if ($modoLotes): ?>
                                <td>
                                    <input type="checkbox" class="doc-checkbox" value="<?= $doc['id'] ?>" 
                                           data-gestion="<?= htmlspecialchars($doc['gestion']) ?>"
                                           data-comprobante="<?= htmlspecialchars($doc['nro_comprobante']) ?>"
                                           data-estado="<?= htmlspecialchars($estado) ?>"
                                           data-contenedor="<?= !empty($doc['contenedor_numero']) ? htmlspecialchars($doc['tipo_contenedor'] . ' #' . $doc['contenedor_numero']) : 'Sin asignar' ?>"
                                           data-ubicacion="<?= htmlspecialchars($doc['ubicacion_nombre'] ?? 'N/A') ?>">
                                </td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?></td>
                            <td><span class="badge" style="background-color: #6c757d;" title="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($abrev) ?></span></td>
                            <td><?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['codigo_abc'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($doc['contenedor_numero'])): ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?></span>
                                <?php else: ?>
                                    Sin asignar
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($doc['ubicacion_nombre'] ?? 'N/A') ?></td>
                            <td>
                                <?php
                                $badgeClass = '';
                                $icon = '';
                                switch($estado) {
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
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $icon ?> <?= htmlspecialchars($estado) ?></span>
                            </td>
                            <td>
                                <a href="/catalogacion/ver/<?= $doc['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                                <a href="/catalogacion/editar/<?= $doc['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                <a href="/catalogacion/eliminar/<?= $doc['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este documento?')" title="Eliminar">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($paginacion['total_pages'] > 1): ?>
            <div class="pagination">
                <?php 
                    $current = $paginacion['page'];
                    $total = $paginacion['total_pages'];
                    $max_visible = 10;
                    
                    // Calcular rango de páginas
                    $start = max(1, $current - floor($max_visible / 2));
                    $end = min($total, $start + $max_visible - 1);
                    
                    if ($end - $start + 1 < $max_visible) {
                        $start = max(1, $end - $max_visible + 1);
                    }
                    
                    // Parametros base
                    $params = array_merge($filtros, ['modo_lotes' => $modoLotes ? '1' : null]);
                ?>

                <!-- Botón Primero -->
                <?php if ($current > 1): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => 1])) ?>" class="btn btn-secondary">⇤ Primero</a>
                <?php endif; ?>

                <!-- Botón Anterior -->
                <?php if ($current > 1): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $current - 1])) ?>" class="btn btn-warning">← Anterior</a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>← Anterior</button>
                <?php endif; ?>
                
                <!-- Números de Página -->
                <div class="pagination-numbers">
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($params, ['page' => $i])) ?>" 
                           class="btn <?= $i == $current ? 'btn-primary active' : 'btn-light' ?> page-num">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <!-- Botón Siguiente -->
                <?php if ($current < $total): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $current + 1])) ?>" class="btn btn-warning">Siguiente →</a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>Siguiente →</button>
                <?php endif; ?>

                <!-- Botón Último -->
                <?php if ($current < $total): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $total])) ?>" class="btn btn-secondary">Último ⇥</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.search-form { padding: 20px; }
.form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-actions { display: flex; gap: 10px; justify-content: center; margin-top: 20px; align-items: center; }
.form-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    font-size: 14px;
    height: 38px; /* Force consistent height */
    box-sizing: border-box;
    text-decoration: none;
    line-height: normal;
    border: 1px solid transparent; /* Ensure border width is accounted for */
    cursor: pointer;
}
.table-responsive { overflow-x: auto; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 15px; padding: 20px; }
.page-info { padding: 8px 16px; }
.badge { background: #1B3C84; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-info { background: #17a2b8; }
.badge-disponible { background: #28a745; } /* Verde */
.badge-falta { background: #dc3545; } /* Rojo */
.badge-prestado { background: #17a2b8; } /* Celeste */
.badge-no-utilizado { background: #ffc107; color: #333; } /* Amarillo */
.badge-anulado { background: #6f42c1; } /* Morado */
.btn-sm { padding: 4px 12px; font-size: 13px; }
.mt-20 { margin-top: 20px; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px; }

/* Colores de filas según estado */
.row-disponible { background-color: #f0fff0; } /* Verde muy claro */
.row-falta { background-color: #ffe6e6; font-weight: 500; } /* Rojo claro */
.row-prestado { background-color: #e6f7ff; } /* Celeste claro */
.row-no-utilizado { background-color: #fff9e6; } /* Amarillo claro */
.row-anulado { background-color: #f3e6ff; } /* Morado claro */

.row-falta td { color: #721c24; }

.header-actions { display: flex; gap: 10px; }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }

.doc-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Pagination Styles */
.pagination { 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    gap: 8px; /* Slightly larger gap between groups */
    padding: 25px 0; 
    flex-wrap: wrap; 
}
.pagination-numbers { 
    display: flex; 
    gap: 2px; /* Small gap between numbers */
    background: #fff;
    padding: 3px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
.btn-light { 
    background: white; 
    border: none; 
    color: #007bff; 
    font-weight: 500;
}
.btn-light:hover {
    background-color: #e9ecef;
    color: #0056b3;
    text-decoration: none;
}
.btn-warning { 
    background: #ffc107; 
    color: #212529; 
    border: 1px solid #ffc107; 
    font-weight: 500;
}
.btn-warning:hover {
    background: #e0a800;
    border-color: #d39e00;
    color: #212529;
}
.btn-secondary {
    background: #6c757d;
    border: 1px solid #6c757d;
    color: white;
}
.btn-secondary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
.pagination .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    min-width: 38px;
    padding: 0 12px;
    border-radius: 4px; /* Slight radius */
    font-size: 14px;
    transition: all 0.2s;
    line-height: normal; /* Fix vertical alignment */
}
.page-num { 
    border-radius: 2px; /* Squared numbers inside the group */
}
.btn-primary.active { 
    background: #1B3C84; 
    border-color: #1B3C84; 
    color: white; 
    cursor: default; 
    z-index: 1;
}
.btn-primary.active:focus {
    box-shadow: none;
}
#seleccionar-todos {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>

<script>
function toggleTodos(checkbox) {
    const checkboxes = document.querySelectorAll('.doc-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function procesarLote() {
    const seleccionados = [];
    document.querySelectorAll('.doc-checkbox:checked').forEach(checkbox => {
        seleccionados.push({
            id: checkbox.value,
            gestion: checkbox.dataset.gestion,
            comprobante: checkbox.dataset.comprobante,
            estado: checkbox.dataset.estado,
            contenedor: checkbox.dataset.contenedor,
            ubicacion: checkbox.dataset.ubicacion
        });
    });
    
    if (seleccionados.length === 0) {
        alert('⚠️ Debes seleccionar al menos un documento');
        return;
    }
    
    // Crear ventana de reporte
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte de Lote - ${seleccionados.length} Documentos</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #1B3C84; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #1B3C84; color: white; }
                .disponible { background: #d4edda; }
                .falta { background: #f8d7da; color: #721c24; font-weight: bold; }
                .prestado { background: #d1ecf1; }
                .no-utilizado { background: #fff3cd; }
                .anulado { background: #e2d9f3; }
                .header { display: flex; justify-content: space-between; align-items: center; }
                @media print {
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📋 Reporte de Lote - Sistema TAMEP</h1>
                <button onclick="window.print()">🖨️ Imprimir</button>
            </div>
            <p><strong>Fecha:</strong> ${new Date().toLocaleString('es-BO')}</p>
            <p><strong>Total documentos seleccionados:</strong> ${seleccionados.length}</p>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Gestión</th>
                        <th>Nro Comprobante</th>
                        <th>Amarro/Libro</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${seleccionados.map((doc, index) => `
                        <tr class="${doc.estado.toLowerCase()}">
                            <td>${index + 1}</td>
                            <td>${doc.gestion}</td>
                            <td>${doc.comprobante}</td>
                            <td>${doc.contenedor}</td>
                            <td>${doc.ubicacion}</td>
                            <td>${doc.estado}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </body>
        </html>
    `);
}
function abrirModalEdicionLote() {
    const seleccionados = [];
    document.querySelectorAll('.doc-checkbox:checked').forEach(checkbox => {
        seleccionados.push(checkbox.value);
    });
    
    if (seleccionados.length === 0) {
        alert('⚠️ Debes seleccionar al menos un documento');
        return;
    }
    
    document.getElementById('ids_lote').value = JSON.stringify(seleccionados);
    document.getElementById('count_seleccionados').textContent = seleccionados.length;
    
    // Show modal
    document.getElementById('modalAsignacion').style.display = 'block';
}

function cerrarModalAsignacion() {
    document.getElementById('modalAsignacion').style.display = 'none';
}
</script>

<?php if ($modoLotes): ?>
<!-- Modal Edición Lote -->
<div id="modalAsignacion" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 50%; max-width: 500px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1B3C84;">Editar Lote de Documentos</h3>
            <span onclick="cerrarModalAsignacion()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>
        
        <form action="/catalogacion/lote/actualizar" method="POST">
            <input type="hidden" name="ids" id="ids_lote">
            
            <p style="margin-bottom: 15px;">Editando <strong><span id="count_seleccionados">0</span></strong> documentos seleccionados.</p>
            <div class="alert alert-info" style="font-size: 0.9em; padding: 10px; margin-bottom: 15px;">
                Nota: Solo se actualizarán los campos que seleccione. Deje en "-- Seleccione --" para mantener el valor actual.
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="contenedor_lote">Contenedor:</label>
                <div style="display:flex; gap:5px;">
                    <select name="contenedor_id" id="contenedor_lote" class="form-control" style="width: 100%; padding: 8px;">
                        <option value="">-- No cambiar --</option>
                        <?php if (isset($contenedores)): ?>
                            <?php foreach ($contenedores as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['tipo_documento_codigo'] ?? 'DOC') ?> <?= $c['gestion'] ?> <?= $c['tipo_contenedor'] ?> #<?= $c['numero'] ?>
                                    <?php if (!empty($c['codigo_abc'])): ?>
                                        (<?= htmlspecialchars($c['codigo_abc']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button type="button" class="btn btn-success" onclick="abrirModalCrearContenedor('contenedor_lote')" title="Crear Nuevo Contenedor">➕</button>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="estado_lote">Estado:</label>
                <select name="estado_documento" id="estado_lote" class="form-control" style="width: 100%; padding: 8px;">
                    <option value="">-- No cambiar --</option>
                    <option value="DISPONIBLE">🟢 Disponible</option>
                    <option value="FALTA">🔴 Falta</option>
                    <option value="PRESTADO">🔵 Prestado</option>
                    <option value="NO UTILIZADO">🟡 No Utilizado</option>
                    <option value="ANULADO">🟣 Anulado</option>
                </select>
            </div>
            
            <div style="text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalAsignacion()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php 
// Include Modal Partial
require __DIR__ . '/../layouts/modal_crear_contenedor.php';

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
