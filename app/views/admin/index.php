<?php include __DIR__ . '/../layout/header.php'; ?>
<h2>Administración</h2>
<div class="row">
    <div class="col-md-6">
        <h4>Configuración de marca</h4>
        <form method="post" action="/index.php?page=admin_settings">
            <div class="mb-2">
                <label class="form-label">Nombre de compañía</label>
                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('company_name', 'AOS')) ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Ruta logo</label>
                <input type="text" name="brand_logo_path" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('brand_logo_path', '/public/assets/aos-logo.svg')) ?>">
            </div>
            <button class="btn btn-primary">Guardar</button>
        </form>
    </div>
    <div class="col-md-6">
        <h4>Usuarios</h4>
        <table class="table table-sm">
            <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr><td><?= htmlspecialchars($u['name']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <form method="post" action="/index.php?page=admin_user_store">
            <div class="row g-2 mb-2">
                <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Nombre"></div>
                <div class="col-md-4"><input type="email" name="email" class="form-control" placeholder="Email"></div>
                <div class="col-md-4"><select name="role" class="form-select">
                    <option value="admin">Admin</option>
                    <option value="requester">Requester</option>
                    <option value="approver">Approver</option>
                    <option value="buyer">Buyer</option>
                    <option value="receiver">Receiver</option>
                    <option value="accountant">Accountant</option>
                </select></div>
            </div>
            <div class="mb-2">
                <input type="password" name="password" class="form-control" placeholder="Contraseña temporal">
            </div>
            <button class="btn btn-secondary">Crear usuario</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
