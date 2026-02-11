<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$providersById = [];
foreach ($providers as $provider) {
    $providersById[(int)$provider['id']] = $provider;
}
$scoresByProvider = [];
foreach (($evaluation['scores'] ?? []) as $score) {
    $scoresByProvider[(int)$score['provider_id']] = $score;
}
?>
<h3>Cotizaciones y Selección de Proveedor - PR #<?= (int)$pr['id'] ?></h3>
<p class="text-muted"><?= htmlspecialchars($pr['title']) ?></p>

<div class="card mb-3">
    <div class="card-body">
        <h5>Agregar cotización</h5>
        <form method="post" action="<?= htmlspecialchars(route_to('provider_quote_store')) ?>" enctype="multipart/form-data">
            <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select class="form-select" name="provider_id" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= (int)$provider['id'] ?>"><?= htmlspecialchars($provider['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de compra</label>
                    <select class="form-select" name="tipo_compra" required>
                        <option value="BIENES">BIENES</option>
                        <option value="SERVICIOS">SERVICIOS</option>
                        <option value="SERVICIOS_TECNICOS">SERVICIOS TÉCNICOS</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Valor</label><input class="form-control" type="number" step="0.01" name="valor" required></div>
                <div class="col-md-1"><label class="form-label">Moneda</label><input class="form-control" name="moneda" value="COP"></div>
                <div class="col-md-2"><label class="form-label">Plazo (días)</label><input class="form-control" type="number" min="0" name="plazo_entrega_dias" required></div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Forma de pago</label>
                    <select class="form-select" name="forma_pago" required>
                        <option value="CONTADO">Contado</option>
                        <option value="CREDICONTADO">Credicontado</option>
                        <option value="CREDITO_30_MAS">Crédito 30+ días</option>
                        <option value="NA">N/A</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2"><label class="form-label">Archivos cotización (PDF/JPG/PNG/XLSX)</label><input class="form-control" type="file" name="quote_files[]" multiple required></div>
                <div class="col-md-3 mt-2"><label class="form-label">Notas</label><input class="form-control" name="notas"></div>
                <div class="col-md-3 mt-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="recotizacion" value="1" id="recotizacionCheck">
                        <label class="form-check-label" for="recotizacionCheck">Marcar como re-cotización (no cuenta para mínimo)</label>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary mt-3">Guardar cotización</button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5>Listado de cotizaciones</h5>
        <table class="table table-sm">
            <thead><tr><th>Proveedor</th><th>Tipo</th><th>Valor</th><th>Plazo</th><th>Pago</th><th>Re-cot.</th><th>Archivos</th></tr></thead>
            <tbody>
            <?php foreach ($quotes as $quote): ?>
                <tr>
                    <td><?= htmlspecialchars($quote['provider_name']) ?></td>
                    <td><?= htmlspecialchars($quote['tipo_compra']) ?></td>
                    <td><?= htmlspecialchars($quote['moneda']) ?> $<?= number_format((float)$quote['valor'], 2) ?></td>
                    <td><?= (int)$quote['plazo_entrega_dias'] ?></td>
                    <td><?= htmlspecialchars($quote['forma_pago']) ?></td>
                    <td><?= (int)$quote['recotizacion'] === 1 ? 'Sí' : 'No' ?></td>
                    <td><?= (int)$quote['file_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($files)): ?>
            <h6>Archivos anexos</h6>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li>
                        <?= htmlspecialchars($file['provider_name']) ?> -
                        <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['original_name']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Evaluación comparativa</h5>
        <form method="post" action="<?= htmlspecialchars(route_to('provider_selection_evaluate')) ?>">
            <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
            <?php $rendered = []; foreach ($quotes as $quote):
                $providerId = (int)$quote['provider_id'];
                if (isset($rendered[$providerId])) { continue; }
                $rendered[$providerId] = true;
                $score = $scoresByProvider[$providerId] ?? null;
                $detail = $score['criterio_detalle'] ?? [];
            ?>
                <fieldset class="border p-2 mb-3">
                    <legend class="float-none w-auto fs-6 px-2"><?= htmlspecialchars($quote['provider_name']) ?></legend>
                    <input type="hidden" name="providers[<?= $providerId ?>][tipo_compra]" value="<?= htmlspecialchars($quote['tipo_compra']) ?>">
                    <div class="row g-2">
                        <div class="col-md-4"><label class="form-label">Experiencia (15%)</label><select class="form-select" name="providers[<?= $providerId ?>][experiencia]"><option value="LT2" <?= ($detail['experiencia'] ?? '') === 'LT2' ? 'selected' : '' ?>>Menor a 2 años (5%)</option><option value="2TO5" <?= ($detail['experiencia'] ?? '') === '2TO5' ? 'selected' : '' ?>>2 a 5 años (10%)</option><option value="GT5" <?= ($detail['experiencia'] ?? '') === 'GT5' ? 'selected' : '' ?>>Mayor a 5 años (15%)</option></select></div>
                        <div class="col-md-4"><label class="form-label">Forma de pago (25%)</label><select class="form-select" name="providers[<?= $providerId ?>][forma_pago]"><option value="CONTADO" <?= ($detail['forma_pago'] ?? '') === 'CONTADO' ? 'selected' : '' ?>>Contado (10%)</option><option value="CREDICONTADO" <?= ($detail['forma_pago'] ?? '') === 'CREDICONTADO' ? 'selected' : '' ?>>Credicontado (20%)</option><option value="CREDITO_30_MAS" <?= ($detail['forma_pago'] ?? '') === 'CREDITO_30_MAS' ? 'selected' : '' ?>>Crédito 30 días o más (25%)</option><option value="NA" <?= ($detail['forma_pago'] ?? '') === 'NA' ? 'selected' : '' ?>>N/A</option></select></div>
                        <div class="col-md-4"><label class="form-label">N/A pago técnico</label><select class="form-select" name="providers[<?= $providerId ?>][forma_pago_na_result]"><option value="CUMPLE" <?= ($detail['forma_pago_na_result'] ?? '') === 'CUMPLE' ? 'selected' : '' ?>>Cumple</option><option value="NO_CUMPLE" <?= ($detail['forma_pago_na_result'] ?? '') === 'NO_CUMPLE' ? 'selected' : '' ?>>No cumple</option></select></div>
                        <div class="col-md-4"><label class="form-label">Entrega (20%)</label><select class="form-select" name="providers[<?= $providerId ?>][entrega]"><option value="MAYOR_10" <?= ($detail['entrega'] ?? '') === 'MAYOR_10' ? 'selected' : '' ?>>Mayor a 10 días (5%)</option><option value="IGUAL_10" <?= ($detail['entrega'] ?? '') === 'IGUAL_10' ? 'selected' : '' ?>>10 días (10%)</option><option value="MENOR_5" <?= ($detail['entrega'] ?? '') === 'MENOR_5' ? 'selected' : '' ?>>Menor a 5 días (20%)</option></select></div>
                        <div class="col-md-4"><label class="form-label">N/A entrega técnico</label><select class="form-select" name="providers[<?= $providerId ?>][entrega_na_result]"><option value="CUMPLE" <?= ($detail['entrega_na_result'] ?? '') === 'CUMPLE' ? 'selected' : '' ?>>Cumple cronograma</option><option value="NO_CUMPLE" <?= ($detail['entrega_na_result'] ?? '') === 'NO_CUMPLE' ? 'selected' : '' ?>>No cumple</option></select></div>
                        <div class="col-md-4"><label class="form-label">Descuento / valor agregado (5%)</label><select class="form-select" name="providers[<?= $providerId ?>][descuento]"><option value="SI" <?= ($detail['descuento'] ?? '') === 'SI' ? 'selected' : '' ?>>Sí (5%)</option><option value="NO" <?= ($detail['descuento'] ?? '') === 'NO' ? 'selected' : '' ?>>No (0%)</option></select></div>
                        <div class="col-md-4"><label class="form-label">Certificaciones técnicas/comerciales (10%)</label><select class="form-select" name="providers[<?= $providerId ?>][certificaciones]"><option value="NINGUNA" <?= ($detail['certificaciones'] ?? '') === 'NINGUNA' ? 'selected' : '' ?>>Ninguna (0%)</option><option value="UNA" <?= ($detail['certificaciones'] ?? '') === 'UNA' ? 'selected' : '' ?>>Una (5%)</option><option value="DOS_MAS" <?= ($detail['certificaciones'] ?? '') === 'DOS_MAS' ? 'selected' : '' ?>>Dos o más (10%)</option></select></div>
                        <div class="col-md-4"><label class="form-label">Precios (25%)</label><select class="form-select" name="providers[<?= $providerId ?>][precios]"><option value="MAYOR" <?= ($detail['precios'] ?? '') === 'MAYOR' ? 'selected' : '' ?>>Mayor (5%)</option><option value="IGUAL" <?= ($detail['precios'] ?? '') === 'IGUAL' ? 'selected' : '' ?>>Igual (15%)</option><option value="MENOR" <?= ($detail['precios'] ?? '') === 'MENOR' ? 'selected' : '' ?>>Menor (25%)</option></select></div>
                        <div class="col-md-4"><label class="form-label">Observación proveedor</label><input class="form-control" name="providers[<?= $providerId ?>][observations]" value="<?= htmlspecialchars($score['observations'] ?? '') ?>"></div>
                    </div>
                    <?php if ($score): ?>
                        <div class="mt-2 small text-muted">Puntaje actual: <?= (int)$score['total_score'] ?> (Precios: <?= (int)$score['precios_score'] ?>)</div>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; ?>

            <div class="mb-2"><label class="form-label">Comentarios adicionales / Observaciones</label><textarea class="form-control" name="observations" rows="3"><?= htmlspecialchars($evaluation['observations'] ?? '') ?></textarea></div>
            <button class="btn btn-outline-primary">Guardar borrador</button>
        </form>

        <hr>
        <form method="post" action="<?= htmlspecialchars(route_to('provider_selection_close')) ?>">
            <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
            <div class="row g-2">
                <div class="col-md-4"><label class="form-label">Ganador manual (opcional)</label><select class="form-select" name="manual_winner_provider_id"><option value="0">Automático por sistema</option><?php foreach ($providers as $provider): ?><option value="<?= (int)$provider['id'] ?>"><?= htmlspecialchars($provider['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-8"><label class="form-label">Justificación</label><input class="form-control" name="tie_break_reason" placeholder="Obligatoria si cambia al ganador automático"></div>
            </div>
            <div class="mt-2"><label class="form-label">Observaciones de cierre</label><textarea class="form-control" name="observations" rows="2"><?= htmlspecialchars($evaluation['observations'] ?? '') ?></textarea></div>
            <button class="btn btn-success mt-3">Cerrar y seleccionar ganador</button>
        </form>

        <?php if (!empty($evaluation['pdf_path'])): ?>
            <a class="btn btn-link mt-2" href="<?= htmlspecialchars(route_to('provider_selection_pdf', ['evaluation_id' => $evaluation['id']])) ?>" target="_blank">Descargar análisis PDF</a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
