<?php include __DIR__ . '/../layout/header.php'; ?>
<h2>OC #<?= $po['id'] ?> - <?= htmlspecialchars($po['supplier_name']) ?></h2>
<p>PR relacionada: <?= htmlspecialchars($po['pr_title']) ?> | Estado: <?= $po['status'] ?></p>
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
    <thead><tr><th>ID</th><th>Fecha</th><th>Cantidad</th></tr></thead>
    <tbody>
    <?php foreach ($receipts as $r): ?>
        <tr><td><?= $r['id'] ?></td><td><?= $r['created_at'] ?></td><td><?= $r['received_qty'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h5>Registrar recepción</h5>
<form method="post" action="/index.php?page=po_receive">
    <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
    <?php for ($i=0;$i<2;$i++): ?>
        <div class="row g-2 mb-2">
            <div class="col-md-8"><input type="text" name="items[<?= $i ?>][description]" class="form-control" placeholder="Descripción"></div>
            <div class="col-md-4"><input type="number" step="0.01" name="items[<?= $i ?>][quantity]" class="form-control" placeholder="Cantidad"></div>
        </div>
    <?php endfor; ?>
    <button class="btn btn-outline-primary">Registrar recepción</button>
</form>
<h4 class="mt-4">Facturas</h4>
<table class="table table-sm">
    <thead><tr><th>Número</th><th>Monto</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $inv): ?>
        <tr><td><?= htmlspecialchars($inv['invoice_number']) ?></td><td>$<?= number_format($inv['amount'],2) ?></td><td><?= $inv['created_at'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<form method="post" action="/index.php?page=po_invoice" class="mb-3">
    <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
    <div class="row g-2 mb-2">
        <div class="col-md-6"><input type="text" name="invoice_number" class="form-control" placeholder="Nº factura"></div>
        <div class="col-md-6"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Monto"></div>
    </div>
    <button class="btn btn-outline-success">Registrar factura</button>
</form>
<h4>Cierre de OC</h4>
<form method="post" action="/index.php?page=po_close">
    <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
    <div class="mb-2"><textarea name="reason" class="form-control" placeholder="Justifique si no hay recepción total"></textarea></div>
    <button class="btn btn-danger">Cerrar OC</button>
</form>
<?php include __DIR__ . '/../layout/footer.php'; ?>
