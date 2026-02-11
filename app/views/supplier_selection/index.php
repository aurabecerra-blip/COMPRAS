<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Módulo B · Evaluación de Selección de Proveedor</h4>
    <span class="badge bg-secondary">Estado: <?= htmlspecialchars($process['status']) ?></span>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Nueva cotización (evidencia)</h6>
                <form method="post" action="<?= htmlspecialchars(route_to('supplier_selection_quote_store')) ?>" enctype="multipart/form-data">
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
                            <label class="form-label">Fecha</label>
                            <input class="form-control" type="date" name="quotation_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor total</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="total_value" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Moneda</label>
                            <input class="form-control" type="text" name="currency" value="COP" maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plazo entrega (días)</label>
                            <input class="form-control" type="number" min="1" name="delivery_term_days" required>
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label">Condiciones de pago</label>
                        <input class="form-control" type="text" name="payment_terms" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Garantía / soporte</label>
                        <input class="form-control" type="text" name="warranty" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cumplimiento técnico</label>
                        <select class="form-select" name="technical_compliance" required>
                            <option value="CUMPLE">Cumple</option>
                            <option value="PARCIAL">Parcial</option>
                            <option value="NO_CUMPLE">No cumple</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Archivo cotización (PDF/imagen/Excel, máx 10MB)</label>
                        <input class="form-control" type="file" name="evidence_file" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar cotización</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Comparativo de cotizaciones</h6>
                <small class="text-muted">Requisito de cierre: mínimo 3 cotizaciones de 3 proveedores diferentes.</small>
                <div class="table-responsive mt-2">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Proveedor</th><th>Valor</th><th>Entrega</th><th>Pago</th><th>Garantía</th><th>Técnico</th><th>Evidencia</th></tr></thead>
                        <tbody>
                        <?php foreach ($quotations as $q): ?>
                            <tr>
                                <td><?= htmlspecialchars($q['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($q['currency']) ?> <?= number_format((float)$q['total_value'], 2) ?></td>
                                <td><?= (int)$q['delivery_term_days'] ?> días</td>
                                <td><?= htmlspecialchars($q['payment_terms']) ?></td>
                                <td><?= htmlspecialchars($q['warranty']) ?></td>
                                <td><?= htmlspecialchars($q['technical_compliance']) ?></td>
                                <td><a target="_blank" href="<?= htmlspecialchars(asset_url($q['evidence_file_path'])) ?>">Ver</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Ranking de proveedores</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>#</th><th>Proveedor</th><th>Precio</th><th>Entrega</th><th>Pago</th><th>Garantía</th><th>Técnico</th><th>Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($scores as $row): ?>
                            <tr class="<?= (int)$row['is_winner'] === 1 ? 'table-success' : '' ?>">
                                <td><?= (int)$row['rank_position'] ?></td>
                                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($row['price_score']) ?></td>
                                <td><?= htmlspecialchars($row['delivery_score']) ?></td>
                                <td><?= htmlspecialchars($row['payment_score']) ?></td>
                                <td><?= htmlspecialchars($row['warranty_score']) ?></td>
                                <td><?= htmlspecialchars($row['technical_score']) ?></td>
                                <td><strong><?= htmlspecialchars($row['total_score']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Cerrar selección y generar acta</h6>
                <form method="post" action="<?= htmlspecialchars(route_to('supplier_selection_decide')) ?>">
                    <input type="hidden" name="purchase_request_id" value="<?= (int)$pr['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label">Ganador manual (opcional, si no es el mayor puntaje)</label>
                        <select class="form-select" name="winner_supplier_id">
                            <option value="0">Automático (mayor puntaje)</option>
                            <?php foreach ($quotations as $q): ?>
                                <option value="<?= (int)$q['supplier_id'] ?>"><?= htmlspecialchars($q['supplier_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Justificación (obligatoria solo si cambia ganador)</label>
                        <textarea class="form-control" name="winner_justification" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observations" rows="2"></textarea>
                    </div>
                    <button class="btn btn-success" type="submit">Generar acta y seleccionar proveedor</button>
                </form>
                <?php if (!empty($process['selection_pdf_path'])): ?>
                    <a class="btn btn-outline-primary btn-sm mt-2" target="_blank" href="<?= htmlspecialchars(asset_url($process['selection_pdf_path'])) ?>">Descargar acta PDF</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
