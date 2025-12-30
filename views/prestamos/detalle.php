<?php 
ob_start(); 
$pageTitle = 'Detalle de Préstamo';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📋 Detalle del Préstamo #<?= $prestamo['id'] ?></h2>
        <div style="display: flex; gap: 10px;">
            <?php if ($prestamo['estado'] === 'Prestado'): ?>
                <button onclick="confirmarDevolucion(<?= $prestamo['id'] ?>)" class="btn btn-success">
                    ✓ Registrar Devolución
                </button>
            <?php endif; ?>
            <a href="/prestamos" class="btn btn-secondary">← Volver al Listado</a>
        </div>
    </div>
    
    <div class="detail-grid">
        <div class="detail-section">
            <h3>Información del Documento</h3>
            <dl class="detail-list">
                <dt>Tipo de Documento:</dt>
                <dd><strong><?= htmlspecialchars($prestamo['tipo_documento'] ?? 'N/A') ?></strong></dd>
                
                <dt>Gestión:</dt>
                <dd><?= htmlspecialchars($prestamo['gestion'] ?? 'N/A') ?></dd>
                
                <dt>Nro de Comprobante:</dt>
                <dd><?= htmlspecialchars($prestamo['nro_comprobante'] ?? 'N/A') ?></dd>
                
                <?php if ($prestamo['tipo_contenedor']): ?>
                <dt>Contenedor:</dt>
                <dd><?= htmlspecialchars($prestamo['tipo_contenedor']) ?> #<?= htmlspecialchars($prestamo['contenedor_numero'] ?? 'N/A') ?></dd>
                <?php endif; ?>
            </dl>
        </div>
        
        <div class="detail-section">
            <h3>Datos del Préstamo</h3>
            <dl class="detail-list">
                <dt>Usuario Solicitante:</dt>
                <dd>
                    <strong><?= htmlspecialchars($prestamo['usuario_nombre'] ?? 'N/A') ?></strong><br>
                    <small>(<?= htmlspecialchars($prestamo['username'] ?? 'N/A') ?>)</small>
                </dd>
                
                <dt>Fecha de Préstamo:</dt>
                <dd><?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></dd>
                
                <dt>Fecha Devolución Estimada:</dt>
                <dd>
                    <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) ?>
                    <?php if ($prestamo['estado'] === 'Prestado' && strtotime($prestamo['fecha_devolucion_esperada']) < time()): ?>
                        <br><span class="badge badge-falta">⚠️ VENCIDO</span>
                    <?php endif; ?>
                </dd>
                
                <?php if ($prestamo['fecha_devolucion_real']): ?>
                <dt>Fecha Devolución Real:</dt>
                <dd><?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])) ?></dd>
                <?php endif; ?>
                
                <dt>Estado:</dt>
                <dd>
                    <?php if ($prestamo['estado'] === 'Prestado'): ?>
                        <span class="badge badge-prestado">📤 Prestado</span>
                    <?php else: ?>
                        <span class="badge badge-disponible">✅ Devuelto</span>
                    <?php endif; ?>
                </dd>
                
                <dt>Registrado por:</dt>
                <dd><?= htmlspecialchars($prestamo['creado_por_nombre'] ?? 'N/A') ?></dd>
            </dl>
        </div>
    </div>
    
    <?php if (!empty($prestamo['observaciones'])): ?>
    <div class="detail-section">
        <h3>Observaciones</h3>
        <p><?= nl2br(htmlspecialchars($prestamo['observaciones'])) ?></p>
    </div>
    <?php endif; ?>
    
    <div class="detail-actions">
        <?php if ($prestamo['estado'] === 'Prestado'): ?>
            <button class="btn btn-success" onclick="confirmarDevolucion(<?= $prestamo['id'] ?>)">
                ✓ Registrar Devolución
            </button>
        <?php endif; ?>
        <a href="/catalogacion/ver/<?= $prestamo['documento_id'] ?>" class="btn btn-primary">
            📄 Ver Documento Completo
        </a>
        <button class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir</button>
        <a href="/prestamos" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.detail-section {
    background: #f5f7fa;
    padding: 20px;
    border-radius: 8px;
}

.detail-section h3 {
    color: #1B3C84;
    margin-bottom: 15px;
    font-size: 18px;
    border-bottom: 2px solid #FFD100;
    padding-bottom: 8px;
}

.detail-list {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    align-items: start;
}

.detail-list dt {
    font-weight: 600;
    color: #333;
}

.detail-list dd {
    margin: 0;
    color: #666;
}

.detail-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding: 20px;
    border-top: 1px solid #ddd;
    margin-top: 20px;
    flex-wrap: wrap;
}

@media print {
    .btn, .detail-actions {
        display: none !important;
    }
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
