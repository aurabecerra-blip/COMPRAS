<?php include __DIR__ . '/../layout/header.php'; ?>
<h2>Cotizaciones para PR #<?= $pr['id'] ?> - <?= htmlspecialchars($pr['title']) ?></h2>
<p>Estado: <?= $pr['status'] ?></p>
<table class="table table-sm">
    <thead><tr><th>Proveedor</th><th>Monto</th><th>Notas</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php foreach ($quotations as $q): ?>
        <tr>
            <td><?= htmlspecialchars($q['supplier_name']) ?></td>
            <td>$<?= number_format($q['amount'], 2) ?></td>
            <td><?= htmlspecialchars($q['notes']) ?></td>
            <td><?= $q['created_at'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h4>Agregar cotización</h4>
<form method="post" action="<?= htmlspecialchars(route_to('quotation_store')) ?>">
    <input type="hidden" name="pr_id" value="<?= $pr['id'] ?>">
    <div class="row g-2 mb-2">
        <div class="col-md-4">
            <select name="supplier_id" class="form-select">
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Monto"></div>
        <div class="col-md-4"><input type="text" name="notes" class="form-control" placeholder="Notas"></div>
    </div>
    <button class="btn btn-primary">Guardar</button>
</form>
<?php if ($pr['status'] === 'APROBADA'): ?>
    <h4 class="mt-4">Generar Orden de Compra</h4>
    <form method="post" action="<?= htmlspecialchars(route_to('purchase_order_create')) ?>">
        <input type="hidden" name="pr_id" value="<?= $pr['id'] ?>">
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label">Proveedor seleccionado</label>
                <select name="supplier_id" class="form-select">
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button class="btn btn-success">Generar OC</button>
    </form>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
