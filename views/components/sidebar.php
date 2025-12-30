<?php
/**
 * Sidebar - Barra lateral de navegación
 */
$user = $user ?? \TAMEP\Core\Session::user();
?>
<div class="sidebar" id="sidebar">
    <!-- Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Ocultar/Mostrar menú">
        <span></span>
        <span></span>
        <span></span>
    </button>
    
    <div class="sidebar-header">
        <img src="/assets/img/logo-tamep.png" alt="TAMEP" class="sidebar-logo">
        <h1>Sistema de Gestion de Archivos</h1>
        <div class="user-info">
            <?= htmlspecialchars($user['nombre_completo']) ?><br>
            <small><?= htmlspecialchars($user['rol']) ?></small>
        </div>
    </div>
    
    <nav>
        <ul>
            <li>
                <a href="/dashboard" class="sidebar-link">
                    <span class="icon">🏠</span>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="/catalogacion" class="sidebar-link">
                    <span class="icon">📂</span>
                    <span>Catalogación</span>
                </a>
            </li>
            
            <li>
                <details <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/prestamos') ? 'open' : '' ?>>
                    <summary class="sidebar-link" style="cursor: pointer;">
                        <span class="icon">📤</span>
                        <span>Préstamos</span>
                    </summary>
                    <ul class="sidebar-submenu">
                        <li><a href="/prestamos">📋 Historial</a></li>
                        <li><a href="/prestamos/nuevo">➕ Nuevo Préstamo</a></li>
                    </ul>
                </details>
            </li>
            
            <li>
                <a href="/reportes" class="sidebar-link">
                    <span class="icon">📊</span>
                    <span>Reportes</span>
                </a>
            </li>
            
            <?php if ($user['rol'] === 'Administrador'): ?>
            <li>
                <a href="/admin/usuarios" class="sidebar-link">
                    <span class="icon">👥</span>
                    <span>Usuarios</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="/normalizacion" class="sidebar-link">
                    <span class="icon">⚙️</span>
                    <span>Normalización</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="/logout" class="sidebar-link">
                <span class="icon">🚪</span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</div>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    // Cargar estado guardado
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
    }
    
    // Toggle al hacer clic
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        // Guardar estado
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });
});
</script>
