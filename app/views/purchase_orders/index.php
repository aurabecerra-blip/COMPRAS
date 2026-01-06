<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
    <h2>Órdenes de compra</h2>
</div>
<table class="table table-striped">
    <thead>
    <tr><th>ID</th><th>Proveedor</th><th>PR</th><th>Estado</th><th>Monto</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td>#<?= $order['id'] ?></td>
            <td><?= htmlspecialchars($order['supplier_name']) ?></td>
            <td><?= htmlspecialchars($order['pr_title']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?></span></td>
            <td>$<?= number_format($order['total_amount'], 2) ?></td>
            <td><a href="<?= htmlspecialchars(route_to('purchase_orders', ['id' => $order['id']])) ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
