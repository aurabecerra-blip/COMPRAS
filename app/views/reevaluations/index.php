<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Módulo A · Reevaluación de Proveedor</h4>
</div>
<?php
$criterionOrder = ['delivery_time', 'quality', 'after_sales', 'sqr', 'documents'];
$criterionNumbers = [
    'delivery_time' => '1',
    'quality' => '2',
    'after_sales' => '3',
    'sqr' => '4',
    'documents' => '5',
];
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= htmlspecialchars(route_to('reevaluation_store')) ?>" id="reevaluation-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select" name="provider_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= (int)$supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?> · <?= htmlspecialchars($supplier['nit'] ?: 'NIT N/D') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha evaluación</label>
                            <input class="form-control" type="date" name="evaluation_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Servicio que presta</label>
                            <input class="form-control" type="text" name="service_provided" maxlength="255" placeholder="Servicio del proveedor">
                        </div>
                    </div>

                    <hr>
                    <h6>Criterios y puntajes</h6>

                    <?php foreach ($criterionOrder as $criterionCode): ?>
                        <?php $criterion = $criteria[$criterionCode]; ?>
                        <div class="border rounded p-3 mb-3">
                            <label class="form-label fw-semibold"><?= $criterionNumbers[$criterionCode] ?>) <?= htmlspecialchars($criterion['name']) ?> (<?= (int)$criterion['weight'] ?>)</label>
                            <select class="form-select js-score-field" name="<?= htmlspecialchars($criterionCode) ?>" data-max="<?= (int)$criterion['weight'] ?>" data-target="score_<?= htmlspecialchars($criterionCode) ?>" required>
                                <option value="">Seleccione...</option>
                                <?php foreach (($criterion['options'] ?? []) as $optionKey => $option): ?>
                                    <option value="<?= htmlspecialchars($optionKey) ?>" data-score="<?= (int)$option['score'] ?>"><?= htmlspecialchars($option['label']) ?> (<?= (int)$option['score'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Puntaje ítem: <span id="score_<?= htmlspecialchars($criterionCode) ?>">0</span></small>
                        </div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observations" rows="3"></textarea>
                    </div>

                    <div class="alert alert-primary">Total en tiempo real: <strong><span id="total_score">0</span> / 100</strong></div>
                    <button class="btn btn-primary" type="submit">Guardar reevaluación</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Histórico</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Fecha</th><th>Proveedor</th><th>Total</th><th>Email</th></tr></thead>
                        <tbody>
                        <?php foreach ($reevaluations as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['evaluation_date']) ?></td>
                                <td><?= htmlspecialchars($row['provider_name']) ?></td>
                                <td><span class="badge bg-primary"><?= (int)$row['total_score'] ?></span></td>
                                <td><?= htmlspecialchars($row['email_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const scoreFields = document.querySelectorAll('.js-score-field');
    const total = document.getElementById('total_score');

    const recalc = () => {
        let totalValue = 0;
        scoreFields.forEach((select) => {
            const option = select.options[select.selectedIndex];
            const score = Number(option?.dataset.score || 0);
            const targetId = select.dataset.target;
            const target = targetId ? document.getElementById(targetId) : null;
            if (target) {
                target.textContent = String(score);
            }
            totalValue += score;
        });

        total.textContent = String(totalValue);
    };

    scoreFields.forEach(el => el.addEventListener('change', recalc));
    recalc();
})();
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
