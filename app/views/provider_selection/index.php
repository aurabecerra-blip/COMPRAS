<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$scoresByProvider = [];
foreach (($evaluation['scores'] ?? []) as $score) {
    $scoresByProvider[(int)$score['provider_id']] = $score;
}

$quotesByProvider = [];
foreach (($latestQuotesByProvider ?? []) as $quote) {
    $quotesByProvider[(int)$quote['provider_id']] = $quote;
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
                <div class="col-md-4">
                    <label class="form-label">Proveedor (editable)</label>
                    <input class="form-control" list="providersList" name="provider_name" placeholder="Escribe el nombre del proveedor" required>
                    <datalist id="providersList">
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= htmlspecialchars($provider['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de compra</label>
                    <select class="form-select js-tipo-compra" name="tipo_compra" required>
                        <option value="BIENES">BIENES</option>
                        <option value="SERVICIOS">SERVICIOS</option>
                        <option value="SERVICIOS_TECNICOS">SERVICIOS TÉCNICOS</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Precio</label><input class="form-control" type="number" step="0.01" name="valor" required></div>
                <div class="col-md-1"><label class="form-label">Moneda</label><input class="form-control" name="moneda" value="COP"></div>
                <div class="col-md-2"><label class="form-label">Plazo (días)</label><input class="form-control" type="number" min="0" name="plazo_entrega_dias" required></div>

                <div class="col-md-3 mt-2">
                    <label class="form-label">Experiencia</label>
                    <select class="form-select" name="experiencia" required>
                        <option value="LT2">Menor de 2 años (5%)</option>
                        <option value="2TO5">2 a 5 años (10%)</option>
                        <option value="GT5">Mayor a 5 años (15%)</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Forma de pago</label>
                    <select class="form-select" name="forma_pago" required>
                        <option value="CONTADO">Contado (10%)</option>
                        <option value="CREDICONTADO">Credicontado (20%)</option>
                        <option value="CREDITO_30_MAS">Crédito 30+ días (25%)</option>
                        <option value="NA">N/A</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Condiciones de entrega</label>
                    <select class="form-select js-entrega" name="entrega" required></select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Cronograma (solo técnicos)</label>
                    <select class="form-select js-entrega-na" name="entrega_na_result">
                        <option value="CUMPLE">Cumple</option>
                        <option value="NO_CUMPLE">No cumple</option>
                    </select>
                </div>

                <div class="col-md-3 mt-2">
                    <label class="form-label">Certificaciones técnicas o comerciales</label>
                    <select class="form-select" name="certificaciones" required>
                        <option value="NINGUNA">Ninguna (0%)</option>
                        <option value="UNA">1 certificación (5%)</option>
                        <option value="DOS_MAS">2 o más certificaciones (10%)</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Descuento</label>
                    <select class="form-select" name="descuento" required>
                        <option value="SI">Sí</option>
                        <option value="NO">No</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2"><label class="form-label">Adjuntos cotización (PDF/JPG/PNG/XLSX)</label><input class="form-control" type="file" name="quote_files[]" multiple required></div>
                <div class="col-md-3 mt-2"><label class="form-label">Notas</label><input class="form-control" name="notas"></div>

                <div class="col-md-6 mt-2 d-flex align-items-end">
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
            <thead><tr><th>Proveedor</th><th>Tipo</th><th>Valor</th><th>Experiencia</th><th>Entrega</th><th>Pago</th><th>Re-cot.</th><th>Archivos</th></tr></thead>
            <tbody>
            <?php foreach ($quotes as $quote): ?>
                <tr>
                    <td><?= htmlspecialchars($quote['provider_name']) ?></td>
                    <td><?= htmlspecialchars($quote['tipo_compra']) ?></td>
                    <td><?= htmlspecialchars($quote['moneda']) ?> $<?= number_format((float)$quote['valor'], 2) ?></td>
                    <td><?= htmlspecialchars((string)$quote['experiencia']) ?></td>
                    <td><?= htmlspecialchars((string)$quote['entrega']) ?></td>
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
        <h5>Análisis automático comparativo</h5>
        <p class="text-muted mb-3">Con la información registrada, el sistema calcula los puntajes sin volver a preguntar criterios.</p>
        <div class="alert alert-info py-2 mb-3">
            <strong>Regla de negocio:</strong> para cerrar la selección, el proveedor ganador debe alcanzar mínimo <strong>75 puntos</strong>.
        </div>

        <table class="table table-bordered table-sm align-middle">
            <thead>
            <tr>
                <th>Proveedor</th><th>Precio</th><th>Precios (25%)</th><th>Exp. (15%)</th><th>Entrega (20%)</th><th>Pago (25%)</th><th>Desc. (5%)</th><th>Cert. (10%)</th><th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($evaluation['scores'] ?? []) as $score):
                $providerId = (int)$score['provider_id'];
                $quote = $quotesByProvider[$providerId] ?? null;
                $detail = $score['criterio_detalle'] ?? [];
            ?>
                <tr>
                    <td><?= htmlspecialchars((string)$score['provider_name']) ?></td>
                    <td><?= htmlspecialchars((string)($quote['moneda'] ?? 'COP')) ?> $<?= number_format((float)($quote['valor'] ?? 0), 2) ?></td>
                    <td><?= (int)$score['precios_score'] ?> (<?= htmlspecialchars((string)($detail['precios'] ?? '')) ?>)</td>
                    <td><?= (int)$score['experiencia_score'] ?></td>
                    <td><?= (int)$score['entrega_score'] ?></td>
                    <td><?= (int)$score['forma_pago_score'] ?></td>
                    <td><?= (int)$score['descuento_score'] ?></td>
                    <td><?= (int)$score['certificaciones_score'] ?></td>
                    <td><strong><?= (int)$score['total_score'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" action="<?= htmlspecialchars(route_to('provider_selection_evaluate')) ?>">
            <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
            <div class="mb-2"><label class="form-label">Comentarios adicionales / Observaciones</label><textarea class="form-control" name="observations" rows="3"><?= htmlspecialchars($evaluation['observations'] ?? '') ?></textarea></div>
            <button class="btn btn-outline-primary">Actualizar análisis automático</button>
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

        <?php if (!empty($evaluation['pdf_path']) || (($evaluation['status'] ?? '') === 'CLOSED')): ?>
            <a class="btn btn-link mt-2" href="<?= htmlspecialchars(route_to('provider_selection_pdf', ['evaluation_id' => $evaluation['id']])) ?>" target="_blank">Descargar PDF de selección</a>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const deliveryOptions = {
        BIENES: [
            {value: 'IGUAL_10', label: '10 días hábiles (5%)'},
            {value: 'MENOR_5', label: 'Menor a 5 días hábiles (20%)'}
        ],
        SERVICIOS: [
            {value: 'MAYOR_10', label: 'Mayor a 10 días (5%)'},
            {value: 'IGUAL_10', label: '10 días hábiles (10%)'},
            {value: 'MENOR_5', label: 'Menor a 5 días (20%)'}
        ],
        SERVICIOS_TECNICOS: [
            {value: 'NA', label: 'N/A (Cronograma de Ejecución)'}
        ]
    };

    const tipo = document.querySelector('.js-tipo-compra');
    const entrega = document.querySelector('.js-entrega');
    const cronograma = document.querySelector('.js-entrega-na');

    function refresh() {
        const selectedType = tipo.value;
        entrega.innerHTML = '';

        (deliveryOptions[selectedType] || []).forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            entrega.appendChild(option);
        });

        const isTechnical = selectedType === 'SERVICIOS_TECNICOS';
        cronograma.disabled = !isTechnical;
    }

    tipo.addEventListener('change', refresh);
    refresh();
})();
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
