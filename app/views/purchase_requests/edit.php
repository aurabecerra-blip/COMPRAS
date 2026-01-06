<?php
$areaOptions = setting_list($settingsRepo, 'form_areas', ['Operaciones', 'Finanzas', 'TI', 'Calidad']);
$costCenters = setting_list($settingsRepo, 'form_cost_centers', ['CC-001', 'CC-002', 'CC-003']);
$statuses = [
    'BORRADOR' => ['label' => 'Borrador', 'icon' => 'bi-pencil', 'color' => 'secondary'],
    'ENVIADA' => ['label' => 'Enviada', 'icon' => 'bi-send', 'color' => 'info'],
    'APROBADA' => ['label' => 'Aprobada', 'icon' => 'bi-check-circle', 'color' => 'success'],
    'RECHAZADA' => ['label' => 'Rechazada', 'icon' => 'bi-x-circle', 'color' => 'danger'],
    'CANCELADA' => ['label' => 'Cancelada', 'icon' => 'bi-slash-circle', 'color' => 'dark'],
];
$steps = ['BORRADOR', 'ENVIADA', 'APROBADA'];
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <p class="text-uppercase text-muted small mb-1">Seguimiento <?= htmlspecialchars($pr['tracking_code']) ?></p>
        <h3 class="mb-0">Solicitud #<?= $pr['id'] ?> - <?= htmlspecialchars($pr['title']) ?></h3>
        <p class="text-muted mb-0">Estado actual: <span class="badge bg-<?= htmlspecialchars($statuses[$pr['status']]['color']) ?>"><?= htmlspecialchars($statuses[$pr['status']]['label']) ?></span></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary"><i class="bi bi-qr-code"></i> Tracking</span>
        <span class="badge bg-success-subtle text-success"><i class="bi bi-shield-check"></i> ISO</span>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="timeline">
            <?php foreach ($steps as $idx => $step): ?>
                <?php
                $completed = array_search($pr['status'], $steps, true) >= $idx || in_array($pr['status'], ['RECHAZADA', 'CANCELADA'], true);
                $active = $pr['status'] === $step;
                ?>
                <div class="timeline-step <?= $completed ? 'completed' : '' ?> <?= $active ? 'active' : '' ?>">
                    <div class="timeline-icon bg-<?= htmlspecialchars($statuses[$step]['color']) ?>">
                        <i class="bi <?= htmlspecialchars($statuses[$step]['icon']) ?>"></i>
                    </div>
                    <div class="timeline-label"><?= htmlspecialchars($statuses[$step]['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($pr['status'] === 'BORRADOR'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_update')) ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $pr['id'] ?>">
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control form-control-lg" value="<?= htmlspecialchars($pr['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Justificación</label>
                <textarea name="justification" class="form-control" rows="3" required><?= htmlspecialchars($pr['justification']) ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Área</label>
                    <input list="areaOptions" type="text" name="area" class="form-control" value="<?= htmlspecialchars($pr['area']) ?>" required>
                    <datalist id="areaOptions">
                        <?php foreach ($areaOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Centro de costo</label>
                    <input list="ccOptions" type="text" name="cost_center" class="form-control" value="<?= htmlspecialchars($pr['cost_center']) ?>" required>
                    <datalist id="ccOptions">
                        <?php foreach ($costCenters as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
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
            <div class="d-flex justify-content-end">
                <button class="btn btn-primary px-4"><i class="bi bi-save"></i> Actualizar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
