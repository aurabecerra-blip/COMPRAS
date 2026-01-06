<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$selectedSupplier = null;
foreach ($suppliers as $s) {
    if ((int)$s['id'] === (int)$pr['selected_supplier_id']) {
        $selectedSupplier = $s;
        break;
    }
}
?>
<h2>Cotizaciones para PR #<?= $pr['id'] ?> - <?= htmlspecialchars($pr['title']) ?></h2>
<p>Estado: <?= $pr['status'] ?></p>
<?php if ($pr['selected_supplier_id']): ?>
    <div class="alert alert-info">
        Proveedor seleccionado: <?= htmlspecialchars($selectedSupplier['name'] ?? '') ?><br>
        Justificación: <?= htmlspecialchars($pr['selection_notes'] ?? '') ?>
    </div>
<?php endif; ?>
<table class="table table-sm">
    <thead><tr><th>Proveedor</th><th>Monto</th><th>Plazo (días)</th><th>PDF</th><th>Notas</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php foreach ($quotations as $q): ?>
        <tr>
            <td><?= htmlspecialchars($q['supplier_name']) ?></td>
            <td>$<?= number_format($q['amount'], 2) ?></td>
            <td><?= htmlspecialchars($q['lead_time_days']) ?></td>
            <td><a href="<?= htmlspecialchars($q['pdf_path']) ?>" target="_blank">Ver PDF</a></td>
            <td><?= htmlspecialchars($q['notes']) ?></td>
            <td><?= $q['created_at'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h4>Agregar cotización</h4>
<form method="post" action="<?= htmlspecialchars(route_to('quotation_store')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="pr_id" value="<?= $pr['id'] ?>">
    <div class="row g-2 mb-2">
        <div class="col-md-4">
            <select name="supplier_id" class="form-select">
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Monto" required></div>
        <div class="col-md-4"><input type="number" step="1" min="0" name="lead_time_days" class="form-control" placeholder="Plazo (días)" required></div>
    </div>
    <div class="mb-2">
        <input type="text" name="notes" class="form-control mb-2" placeholder="Notas">
        <input type="file" name="quotation_pdf" class="form-control" accept="application/pdf" required>
    </div>
    <button class="btn btn-primary">Guardar</button>
</form>
<?php if ($pr['status'] === 'APROBADA'): ?>
    <h4 class="mt-4">Selección de proveedor</h4>
    <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_select_supplier')) ?>" class="mb-3">
        <input type="hidden" name="pr_id" value="<?= $pr['id'] ?>">
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label">Proveedor</label>
                <select name="supplier_id" class="form-select">
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $pr['selected_supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Justificación</label>
                <textarea name="selection_notes" class="form-control" rows="2" required><?= htmlspecialchars($pr['selection_notes'] ?? '') ?></textarea>
            </div>
        </div>
        <button class="btn btn-outline-primary">Guardar selección</button>
    </form>
    <h4 class="mt-4">Generar Orden de Compra</h4>
    <form method="post" action="<?= htmlspecialchars(route_to('purchase_order_create')) ?>">
        <input type="hidden" name="pr_id" value="<?= $pr['id'] ?>">
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label">Proveedor seleccionado</label>
                <select name="supplier_id" class="form-select">
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $pr['selected_supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Justificación de selección</label>
                <textarea name="selection_notes" class="form-control" rows="2" required><?= htmlspecialchars($pr['selection_notes'] ?? '') ?></textarea>
            </div>
        </div>
        <button class="btn btn-success">Generar OC</button>
    </form>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
