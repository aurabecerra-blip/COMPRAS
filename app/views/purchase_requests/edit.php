<?php include __DIR__ . '/../layout/header.php'; ?>
<h2>Solicitud #<?= $pr['id'] ?> - <?= htmlspecialchars($pr['title']) ?></h2>
<p>Estado actual: <strong><?= $pr['status'] ?></strong></p>
<?php if ($pr['status'] === 'BORRADOR'): ?>
<form method="post" action="<?= htmlspecialchars(route_to('purchase_request_update')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $pr['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($pr['title']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($pr['description']) ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Ítems</label>
        <?php foreach ($pr['items'] as $idx => $item): ?>
            <div class="row g-2 mb-2">
                <div class="col-md-6"><input type="text" name="items[<?= $idx ?>][description]" class="form-control" value="<?= htmlspecialchars($item['description']) ?>"></div>
                <div class="col-md-3"><input type="number" step="0.01" name="items[<?= $idx ?>][quantity]" class="form-control" value="<?= $item['quantity'] ?>"></div>
                <div class="col-md-3"><input type="number" step="0.01" name="items[<?= $idx ?>][unit_price]" class="form-control" value="<?= $item['unit_price'] ?>"></div>
            </div>
        <?php endforeach; ?>
        <div class="row g-2 mb-2">
            <div class="col-md-6"><input type="text" name="items[new][description]" class="form-control" placeholder="Descripción"></div>
            <div class="col-md-3"><input type="number" step="0.01" name="items[new][quantity]" class="form-control" placeholder="Cantidad"></div>
            <div class="col-md-3"><input type="number" step="0.01" name="items[new][unit_price]" class="form-control" placeholder="Precio Unit."></div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Adjunto</label>
        <input type="file" name="attachment" class="form-control">
    </div>
    <button class="btn btn-primary">Actualizar</button>
</form>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
