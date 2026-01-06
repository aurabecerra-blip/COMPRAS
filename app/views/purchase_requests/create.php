<?php include __DIR__ . '/../layout/header.php'; ?>
<h2>Nueva Solicitud de Compra</h2>
<form method="post" action="/index.php?page=purchase_request_store" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control" rows="3"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Ítems</label>
        <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="row g-2 mb-2">
                <div class="col-md-6"><input type="text" name="items[<?= $i ?>][description]" class="form-control" placeholder="Descripción"></div>
                <div class="col-md-3"><input type="number" step="0.01" name="items[<?= $i ?>][quantity]" class="form-control" placeholder="Cantidad"></div>
                <div class="col-md-3"><input type="number" step="0.01" name="items[<?= $i ?>][unit_price]" class="form-control" placeholder="Precio Unit."></div>
            </div>
        <?php endfor; ?>
    </div>
    <div class="mb-3">
        <label class="form-label">Adjunto (PDF/imagen)</label>
        <input type="file" name="attachment" class="form-control" accept="application/pdf,image/*">
    </div>
    <button class="btn btn-primary">Guardar</button>
</form>
<?php include __DIR__ . '/../layout/footer.php'; ?>
