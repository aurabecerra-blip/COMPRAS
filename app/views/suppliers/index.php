<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
    <h2>Proveedores</h2>
</div>
<table class="table table-striped">
    <thead><tr><th>Nombre</th><th>Contacto</th><th>Email</th><th>Teléfono</th></tr></thead>
    <tbody>
        <?php foreach ($suppliers as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['contact']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h4>Nuevo proveedor</h4>
<form method="post" action="<?= htmlspecialchars(route_to('supplier_store')) ?>">
    <div class="row g-2 mb-2">
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nombre" required></div>
        <div class="col-md-3"><input type="text" name="contact" class="form-control" placeholder="Contacto"></div>
        <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email"></div>
        <div class="col-md-3"><input type="text" name="phone" class="form-control" placeholder="Teléfono"></div>
    </div>
    <button class="btn btn-primary">Guardar</button>
</form>
<?php include __DIR__ . '/../layout/footer.php'; ?>
