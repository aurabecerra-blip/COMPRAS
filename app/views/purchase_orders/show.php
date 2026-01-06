<?php include __DIR__ . '/../layout/header.php'; ?>
<?php $statusLabel = str_replace('_', ' ', $po['status']); ?>
<h2>OC #<?= $po['id'] ?> - <?= htmlspecialchars($po['supplier_name']) ?></h2>
<p>PR relacionada: <?= htmlspecialchars($po['pr_title']) ?> | Estado: <?= htmlspecialchars($statusLabel) ?></p>
<?php if ($po['status'] === 'CREADA'): ?>
    <form method="post" action="<?= htmlspecialchars(route_to('po_send')) ?>" class="mb-3">
        <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
        <button class="btn btn-sm btn-warning">Marcar como enviada a proveedor</button>
    </form>
<?php endif; ?>
<table class="table table-sm">
    <thead><tr><th>Descripción</th><th>Cantidad</th><th>Precio Unit</th><th>Total</th></tr></thead>
    <tbody>
    <?php $total = 0; foreach ($po['items'] as $item): $rowTotal = $item['quantity'] * $item['unit_price']; $total += $rowTotal; ?>
        <tr>
            <td><?= htmlspecialchars($item['description']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td>$<?= number_format($item['unit_price'],2) ?></td>
            <td>$<?= number_format($rowTotal,2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="3">Total</th><th>$<?= number_format($total,2) ?></th></tr></tfoot>
</table>
<h4>Recepciones</h4>
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Fecha</th><th>Cantidad</th><th>Evidencia</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($receipts as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['created_at'] ?></td>
            <td><?= $r['received_qty'] ?></td>
            <td><?= $r['evidence_path'] ? '<a href="'.htmlspecialchars($r['evidence_path']).'" target="_blank">Ver</a>' : '-' ?></td>
            <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h5>Registrar recepción</h5>
<form method="post" action="<?= htmlspecialchars(route_to('po_receive')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
    <?php foreach ($po['items'] as $item): ?>
        <div class="row g-2 mb-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-0"><?= htmlspecialchars($item['description']) ?> (Pendiente: <?= $item['quantity'] ?>)</label>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" min="0" name="items[<?= $item['id'] ?>]" class="form-control" placeholder="Cantidad a recibir">
            </div>
        </div>
    <?php endforeach; ?>
    <div class="mb-2">
        <label class="form-label">Evidencia (opcional)</label>
        <input type="file" name="evidence" class="form-control" accept="application/pdf,image/*">
    </div>
    <div class="mb-2">
        <label class="form-label">Notas</label>
        <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>
    <button class="btn btn-outline-primary">Registrar recepción</button>
</form>
<h4 class="mt-4">Cierre de OC</h4>
<form method="post" action="<?= htmlspecialchars(route_to('po_close')) ?>">
    <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
    <div class="mb-2"><textarea name="reason" class="form-control" placeholder="Justifique si no hay recepción total"></textarea></div>
    <button class="btn btn-danger">Cerrar OC</button>
</form>
<?php include __DIR__ . '/../layout/footer.php'; ?>
