<?php include __DIR__ . '/../layout/header.php'; ?>
<?php $search = trim($_GET['search'] ?? ''); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Configuración → Usuarios</h2>
    <a href="<?= htmlspecialchars(route_to('admin')) ?>" class="btn btn-outline-secondary">Volver a administración</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5>Buscar usuarios</h5>
        <form method="get" class="row g-2">
            <input type="hidden" name="page" value="admin_users">
            <div class="col-md-10">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Nombre, email o rol">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5>Crear usuario</h5>
        <form method="post" action="<?= htmlspecialchars(route_to('admin_user_store')) ?>">
            <div class="row g-2 mb-2">
                <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Nombre" required></div>
                <div class="col-md-4">
                    <input type="email" name="email" class="form-control" placeholder="Email" pattern="^[^@\s]+@aossas\.com$" required>
                </div>
                <div class="col-md-4">
                    <select name="role" class="form-select" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars(ucfirst($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-4"><input type="password" name="password" class="form-control" placeholder="Contraseña" required></div>
                <div class="col-md-8 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="new_is_active" checked>
                        <label class="form-check-label" for="new_is_active">Activo</label>
                    </div>
                </div>
            </div>
            <button class="btn btn-success">Crear</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Listado</h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Actualizar</th></tr></thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="text-muted">No hay usuarios para mostrar.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <form method="post" action="<?= htmlspecialchars(route_to('admin_user_update')) ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <td><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($u['name']) ?>" required></td>
                            <td><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($u['email']) ?>" pattern="^[^@\s]+@aossas\.com$" required></td>
                            <td>
                                <select name="role" class="form-select form-select-sm" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role) ?>" <?= $u['role'] === $role ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" <?= !empty($u['is_active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= !empty($u['is_active']) ? 'Activo' : 'Inactivo' ?></label>
                                </div>
                            </td>
                            <td>
                                <input type="password" name="new_password" class="form-control form-control-sm mb-1" placeholder="Reset contraseña (opcional)">
                                <button class="btn btn-primary btn-sm w-100">Guardar</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
