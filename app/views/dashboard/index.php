<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row">
    <div class="col-md-4">
        <div class="card text-bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Solicitudes</h5>
                <p class="card-text display-6"><?= $stats['prs'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">OC generadas</h5>
                <p class="card-text display-6"><?= $stats['pos'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Facturas registradas</h5>
                <p class="card-text display-6"><?= $stats['invoices'] ?></p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
