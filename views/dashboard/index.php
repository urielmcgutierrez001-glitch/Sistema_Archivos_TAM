<?php 
ob_start(); 
$pageTitle = 'Dashboard';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Documentos</h3>
        <div class="number"><?= isset($stats['total_documentos']) ? number_format($stats['total_documentos']) : '0' ?></div>
    </div>
    
    <div class="stat-card yellow">
        <h3>Total Contenedores</h3>
        <div class="number"><?= isset($stats['total_contenedores']) ? number_format($stats['total_contenedores']) : '0' ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Libros</h3>
        <div class="number"><?= isset($stats['total_libros']) ? number_format($stats['total_libros']) : '0' ?></div>
    </div>
    
    <div class="stat-card yellow">
        <h3>Amarros</h3>
        <div class="number"><?= isset($stats['total_amarros']) ? number_format($stats['total_amarros']) : '0' ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Préstamos Activos</h3>
        <div class="number"><?= isset($stats['prestamos_activos']) ? number_format($stats['prestamos_activos']) : '0' ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Bienvenido al Sistema TAMEP</h2>
    </div>
    <p>Sistema de Gestión Documental y Control de Préstamos</p>
    <p>Usuario: <strong><?= isset($user['nombre_completo']) ? htmlspecialchars($user['nombre_completo']) : 'Usuario' ?></strong></p>
    <p>Rol: <strong><?= isset($user['rol']) ? htmlspecialchars($user['rol']) : 'N/A' ?></strong></p>
    
    <div class="mt-20">
        <h3 style="color: #1B3C84; margin-bottom: 15px;">Módulos Disponibles:</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="/catalogacion" class="btn btn-primary" style="text-align: center;">
                📚 Catalogación y Búsqueda
            </a>
            <a href="/prestamos" class="btn btn-secondary" style="text-align: center;">
                📤 Control de Préstamos
            </a>
            <a href="/reportes" class="btn btn-primary" style="text-align: center;">
                📊 Reportes de Gestión
            </a>
            <?php if (isset($user['rol']) && $user['rol'] === 'Administrador'): ?>
            <a href="/admin/usuarios" class="btn btn-secondary" style="text-align: center;">
                👥 Gestión de Usuarios
            </a>
            <a href="/normalizacion" class="btn btn-primary" style="text-align: center;">
                ⚙️ Normalización de Datos
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-20">
    <h3 style="color: #1B3C84;">Características del Sistema:</h3>
    <ul style="line-height: 2;">
        <li>✅ Catalogación y búsqueda avanzada de documentos</li>
        <li>✅ Control de préstamos con validación LIBRO/AMARRO</li>
        <li>✅ Sistema de alertas de vencimiento</li>
        <li>✅ Reportes de trazabilidad y métricas</li>
        <li>✅ Gestión de usuarios con roles (Administrador, Usuario, Consulta)</li>
        <li>✅ Normalización y validación de datos</li>
        <li>✅ Interfaz responsive con colores institucionales TAMEP</li>
    </ul>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
