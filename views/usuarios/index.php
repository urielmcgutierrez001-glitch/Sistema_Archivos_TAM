<?php 
ob_start(); 
$pageTitle = 'Gestión de Usuarios';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>👥 Gestión de Usuarios</h2>
        <a href="/admin/usuarios/crear" class="btn btn-primary">➕ Nuevo Usuario</a>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nombre Completo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay usuarios registrados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usr): ?>
                        <tr>
                            <td><?= $usr['id'] ?></td>
                            <td><strong><?= htmlspecialchars($usr['username']) ?></strong></td>
                            <td><?= htmlspecialchars($usr['nombre_completo']) ?></td>
                            <td>
                                <span class="badge <?= $usr['rol'] === 'Administrador' ? 'badge-admin' : 'badge-user' ?>">
                                    <?= htmlspecialchars($usr['rol']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usr['activo']): ?>
                                    <span class="badge badge-disponible">✓ Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-anulado">✗ Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/usuarios/editar/<?= $usr['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                <?php if ($usr['id'] != $user['id']): ?>
                                    <button onclick="confirmarEliminacion(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['username']) ?>')" 
                                            class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.badge-admin {
    background: #1B3C84;
    color: white;
}

.badge-user {
    background: #17a2b8;
    color: white;
}
</style>

<script>
function confirmarEliminacion(id, username) {
    if (confirm(`¿Está seguro que desea eliminar al usuario "${username}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = '/admin/usuarios/eliminar/' + id;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
