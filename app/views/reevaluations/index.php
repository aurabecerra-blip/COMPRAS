<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Módulo A · Reevaluación de Proveedor</h4>
</div>
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

                    <div class="border rounded p-3 mb-3">
                        <label class="form-label fw-semibold">1) Cumple con los tiempos de entrega (20)</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input js-delivery-mode" type="radio" name="delivery_mode" value="on_time" checked>
                                <label class="form-check-label">A tiempo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input js-delivery-mode" type="radio" name="delivery_mode" value="breach">
                                <label class="form-check-label">Incumplimiento</label>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label">Número incumplimientos</label>
                            <input type="number" class="form-control" name="delivery_breaches" id="delivery_breaches" min="0" value="0" disabled>
                        </div>
                        <small class="text-muted">Puntaje ítem: <span id="score_delivery">20</span></small>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <label class="form-label fw-semibold">2) Calidad del producto o servicio (40)</label>
                        <select class="form-select js-score-field" name="quality" data-max="40" required>
                            <option value="">Seleccione...</option>
                            <option value="meets" data-score="40">Cumple con los requisitos</option>
                            <option value="not_meets" data-score="0">No cumple</option>
                        </select>
                        <small class="text-muted">Puntaje ítem: <span id="score_quality">0</span></small>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <label class="form-label fw-semibold">3) Postventa y garantías (10)</label>
                        <select class="form-select js-score-field" name="after_sales" data-max="10" required>
                            <option value="">Seleccione...</option>
                            <option value="full" data-score="10">Cumple oportunamente con todas las garantías y soporte técnico</option>
                            <option value="partial" data-score="5">Cumple parcialmente con garantías y soporte técnico</option>
                            <option value="none" data-score="0">No cumple con garantías ni soporte técnico</option>
                        </select>
                        <small class="text-muted">Puntaje ítem: <span id="score_after_sales">0</span></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observations" rows="3"></textarea>
                    </div>

                    <div class="alert alert-primary">Total en tiempo real: <strong><span id="total_score">20</span> / 100</strong></div>
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
    const deliveryRadios = document.querySelectorAll('.js-delivery-mode');
    const breachesInput = document.getElementById('delivery_breaches');
    const qualitySelect = document.querySelector('select[name="quality"]');
    const afterSalesSelect = document.querySelector('select[name="after_sales"]');

    const scoreDelivery = document.getElementById('score_delivery');
    const scoreQuality = document.getElementById('score_quality');
    const scoreAfterSales = document.getElementById('score_after_sales');
    const total = document.getElementById('total_score');

    const getSelectScore = (select) => {
        const option = select.options[select.selectedIndex];
        return Number(option?.dataset.score || 0);
    };

    const recalc = () => {
        let delivery = 20;
        const breachMode = [...deliveryRadios].find(r => r.checked)?.value === 'breach';
        breachesInput.disabled = !breachMode;
        if (breachMode) {
            const breaches = Math.max(0, Number(breachesInput.value || 0));
            delivery = Math.max(0, 20 - (breaches * 2));
        }

        const quality = getSelectScore(qualitySelect);
        const afterSales = getSelectScore(afterSalesSelect);
        const totalValue = delivery + quality + afterSales;

        scoreDelivery.textContent = String(delivery);
        scoreQuality.textContent = String(quality);
        scoreAfterSales.textContent = String(afterSales);
        total.textContent = String(totalValue);
    };

    deliveryRadios.forEach(el => el.addEventListener('change', recalc));
    breachesInput.addEventListener('input', recalc);
    qualitySelect.addEventListener('change', recalc);
    afterSalesSelect.addEventListener('change', recalc);
    recalc();
})();
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
