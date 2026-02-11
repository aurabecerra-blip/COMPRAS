<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$status = $process['status'] ?? 'BORRADOR';
$statusLabels = [
    'BORRADOR' => 'Borrador',
    'EN_EVALUACION' => 'En evaluación',
    'SELECCIONADO' => 'Seleccionado',
    'ANULADO' => 'Anulado',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Selección de Proveedor por Cotizaciones · PR #<?= (int)$pr['id'] ?></h4>
    <span class="badge bg-secondary">Estado: <?= htmlspecialchars($statusLabels[$status] ?? $status) ?></span>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Registrar cotización</h6>
                <form method="post" action="<?= htmlspecialchars(route_to('supplier_selection_quote_store')) ?>" enctype="multipart/form-data" id="quotation-form">
                    <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" name="supplier_id" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= (int)$supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Fecha cotización</label>
                            <input class="form-control" type="date" name="quotation_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Moneda</label>
                            <input class="form-control" type="text" name="currency" value="COP" maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor subtotal</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="valor_subtotal" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor total</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="valor_total" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plazo entrega (días)</label>
                            <input class="form-control" type="number" min="1" name="delivery_term_days" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Experiencia (años)</label>
                            <input class="form-control" type="number" min="0" name="experiencia_anios" required>
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label">Condiciones de pago (texto)</label>
                        <input class="form-control" type="text" name="payment_terms" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Evaluación condiciones de pago (10)</label>
                        <select class="form-select" name="evaluacion_pago" required>
                            <option value="MUY_FAVORABLES">Muy favorables (10)</option>
                            <option value="ACEPTABLES" selected>Aceptables (5)</option>
                            <option value="POCO_FAVORABLES">Poco favorables (0)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Garantía / postventa (texto)</label>
                        <input class="form-control" type="text" name="warranty" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Servicio postventa / garantías (10)</label>
                        <select class="form-select" name="evaluacion_postventa" required>
                            <option value="CUMPLE_TOTAL">Cumple oportunamente con todas las garantías y soporte técnico (10)</option>
                            <option value="CUMPLE_PARCIAL" selected>Cumple parcialmente con las garantías y soporte técnico (5)</option>
                            <option value="NO_CUMPLE">No cumple con las garantías ni soporte técnico (0)</option>
                        </select>
                    </div>

                    <div class="form-check mb-2 mt-2">
                        <input class="form-check-input" type="checkbox" name="ofrece_descuento" id="ofrece_descuento">
                        <label class="form-check-label" for="ofrece_descuento">Ofrece descuento</label>
                    </div>
                    <div class="row g-2 mb-2" id="discount-fields" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label">Tipo descuento</label>
                            <select class="form-select" name="tipo_descuento">
                                <option value="PORCENTAJE">Porcentaje</option>
                                <option value="VALOR">Valor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Descuento valor</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="descuento_valor">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6 form-check">
                            <input class="form-check-input" type="checkbox" name="certificaciones_tecnicas" id="cert_tecnicas">
                            <label class="form-check-label" for="cert_tecnicas">Certificaciones técnicas</label>
                        </div>
                        <div class="col-md-6 form-check">
                            <input class="form-check-input" type="checkbox" name="certificaciones_comerciales" id="cert_comerciales">
                            <label class="form-check-label" for="cert_comerciales">Certificaciones comerciales</label>
                        </div>
                    </div>
                    <div class="mb-2" id="cert-list-wrapper" style="display:none;">
                        <label class="form-label">Lista certificaciones</label>
                        <textarea class="form-control" name="lista_certificaciones" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Archivo cotización (obligatorio)</label>
                        <input class="form-control" type="file" name="archivo_cotizacion" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Archivo soporte experiencia (opcional)</label>
                        <input class="form-control" type="file" name="archivo_soporte_experiencia">
                    </div>
                    <div class="mb-2" id="cert-file-wrapper" style="display:none;">
                        <label class="form-label">Archivo certificaciones (obligatorio si aplica)</label>
                        <input class="form-control" type="file" name="archivo_certificaciones">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observations" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar cotización</button>
                </form>
                <div class="alert alert-light border mt-3 mb-0" id="live-score-preview">
                    Vista rápida (JS): complete valores para ver precio neto y puntaje estimado.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Comparativo de cotizaciones</h6>
                <small class="text-muted">Se exige mínimo 3 cotizaciones de 3 proveedores distintos para poder seleccionar ganador.</small>
                <div class="table-responsive mt-2">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Proveedor</th><th>Total</th><th>Desc.</th><th>Entrega</th><th>Exp.</th><th>Pago</th><th>Postventa</th><th>Cert.</th><th>Evidencias</th></tr></thead>
                        <tbody>
                        <?php foreach ($quotations as $q): ?>
                            <tr>
                                <td><?= htmlspecialchars($q['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($q['currency']) ?> <?= number_format((float)$q['valor_total'], 2) ?></td>
                                <td><?= (int)$q['ofrece_descuento'] === 1 ? htmlspecialchars($q['tipo_descuento'] . ' ' . $q['descuento_valor']) : 'No' ?></td>
                                <td><?= (int)$q['delivery_term_days'] ?> días</td>
                                <td><?= (int)$q['experiencia_anios'] ?> años</td>
                                <td><?= htmlspecialchars($q['evaluacion_pago']) ?></td>
                                <td><?= htmlspecialchars($q['evaluacion_postventa']) ?></td>
                                <td><?= ((int)$q['certificaciones_tecnicas'] || (int)$q['certificaciones_comerciales']) ? 'Sí' : 'No' ?></td>
                                <td>
                                    <?php foreach ($q['files'] as $file): ?>
                                        <div><a target="_blank" href="<?= htmlspecialchars(asset_url($file['file_path'])) ?>"><?= htmlspecialchars($file['original_name']) ?></a></div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Ranking (backend)</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>#</th><th>Proveedor</th><th>Total</th><th>Detalle</th></tr></thead>
                        <tbody>
                        <?php foreach ($scores as $row): ?>
                            <tr class="<?= (int)$row['is_winner'] === 1 ? 'table-success' : '' ?>">
                                <td><?= (int)$row['rank_position'] ?></td>
                                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                                <td><strong><?= number_format((float)$row['total_score'], 2) ?></strong></td>
                                <td>
                                    <?php foreach ($row['details'] as $detail): ?>
                                        <?php if (($detail['criterion_code'] ?? '') !== 'TOTAL'): ?>
                                            <span class="badge text-bg-light"><?= htmlspecialchars($detail['criterion_name']) ?>: <?= number_format((float)$detail['score_value'], 2) ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Selección de ganador y Acta PDF</h6>
                <form method="post" action="<?= htmlspecialchars(route_to('supplier_selection_decide')) ?>">
                    <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label">Ganador manual (opcional; por defecto gana el mayor puntaje)</label>
                        <select class="form-select" name="winner_supplier_id">
                            <option value="0">Automático (#1 puntaje)</option>
                            <?php foreach ($quotations as $q): ?>
                                <option value="<?= (int)$q['supplier_id'] ?>"><?= htmlspecialchars($q['supplier_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Justificación (obligatoria si no elige al #1)</label>
                        <textarea class="form-control" name="winner_justification" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observations" rows="2"></textarea>
                    </div>
                    <button class="btn btn-success" type="submit">Seleccionar proveedor y generar acta</button>
                </form>
                <?php if (!empty($process['acta_pdf_url'])): ?>
                    <a class="btn btn-outline-primary btn-sm mt-2" target="_blank" href="<?= htmlspecialchars(route_to('supplier_selection_pdf', ['id' => $pr['id']])) ?>">Ver Acta PDF</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const discountCheck = document.getElementById('ofrece_descuento');
    const discountFields = document.getElementById('discount-fields');
    const certTech = document.getElementById('cert_tecnicas');
    const certCom = document.getElementById('cert_comerciales');
    const certList = document.getElementById('cert-list-wrapper');
    const certFile = document.getElementById('cert-file-wrapper');
    const preview = document.getElementById('live-score-preview');
    const form = document.getElementById('quotation-form');

    const quotationTotals = <?= json_encode(array_map(fn($q) => [
        'valor_total' => (float)$q['valor_total'],
        'delivery_term_days' => (int)$q['delivery_term_days'],
    ], $quotations), JSON_UNESCAPED_UNICODE) ?>;

    function toggleDiscount() {
        discountFields.style.display = discountCheck.checked ? 'flex' : 'none';
    }

    function toggleCerts() {
        const active = certTech.checked || certCom.checked;
        certList.style.display = active ? 'block' : 'none';
        certFile.style.display = active ? 'block' : 'none';
    }

    function livePreview() {
        const total = parseFloat(form.querySelector('[name="valor_total"]').value || '0');
        const days = parseFloat(form.querySelector('[name="delivery_term_days"]').value || '0');
        const hasDiscount = discountCheck.checked;
        const discountType = form.querySelector('[name="tipo_descuento"]').value;
        const discountVal = parseFloat(form.querySelector('[name="descuento_valor"]').value || '0');
        const payment = form.querySelector('[name="evaluacion_pago"]').value;
        const postSale = form.querySelector('[name="evaluacion_postventa"]').value;
        const years = parseInt(form.querySelector('[name="experiencia_anios"]').value || '0', 10);

        let discount = 0;
        if (hasDiscount) {
            discount = discountType === 'PORCENTAJE' ? total * (discountVal / 100) : discountVal;
        }

        const net = Math.max(0, total - discount);
        const minNet = Math.min(...quotationTotals.map(q => q.valor_total), net || 0).toFixed(2);
        const minDays = Math.min(...quotationTotals.map(q => q.delivery_term_days), days || 1);

        const paymentScore = payment === 'MUY_FAVORABLES' ? 10 : payment === 'ACEPTABLES' ? 5 : 0;
        const postScore = postSale === 'CUMPLE_TOTAL' ? 10 : postSale === 'CUMPLE_PARCIAL' ? 5 : 0;
        const expScore = years >= 5 ? 10 : years >= 3 ? 7 : years >= 1 ? 4 : 0;

        const priceScore = net > 0 && Number(minNet) > 0 ? (35 * (Number(minNet) / net)) : 0;
        const deliveryScore = days > 0 ? (20 * (minDays / days)) : 0;

        const totalScore = priceScore + deliveryScore + paymentScore + postScore + expScore;
        preview.innerHTML = `Vista rápida (JS): Precio neto <b>${net.toFixed(2)}</b> · Puntaje estimado parcial <b>${totalScore.toFixed(2)}</b>/100 (sin comparar descuento/certificaciones contra todos los proveedores).`;
    }

    ['input', 'change'].forEach(evt => form.addEventListener(evt, livePreview));
    discountCheck.addEventListener('change', toggleDiscount);
    certTech.addEventListener('change', toggleCerts);
    certCom.addEventListener('change', toggleCerts);

    toggleDiscount();
    toggleCerts();
})();
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
