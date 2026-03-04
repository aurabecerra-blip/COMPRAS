<?php include __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <p class="text-uppercase text-muted small mb-1">Módulo ISO · Evaluación de proveedores</p>
        <h3 class="mb-0">Evaluación de proveedores</h3>
        <p class="text-muted mb-0">Registro histórico por proveedor y fecha con cálculo ponderado automático.</p>
        <p class="text-muted mb-0 small">Puntaje mínimo para aprobar: <strong>80%</strong>.</p>
    </div>
    <a class="btn btn-outline-success" href="<?= htmlspecialchars(route_to('supplier_evaluations_export')) ?>">
        <i class="bi bi-file-earmark-excel"></i> Exportar evaluaciones (Excel)
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Nueva evaluación</h5>
                <form action="<?= htmlspecialchars(route_to('supplier_evaluation_store')) ?>" method="post" class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Proveedor</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= (int)$supplier['id'] ?>">
                                    <?= htmlspecialchars($supplier['name']) ?> · NIT <?= htmlspecialchars($supplier['nit'] ?: 'N/D') ?> · <?= htmlspecialchars($supplier['service'] ?: 'Sin servicio') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">1) Cumple con los tiempos de entrega (20%)</label>
                        <div class="border rounded p-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_mode" id="delivery_on_time" value="on_time" checked>
                                <label class="form-check-label" for="delivery_on_time">A tiempo (20 puntos)</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" name="delivery_mode" id="delivery_breach" value="breach">
                                <label class="form-check-label" for="delivery_breach">Incumplimiento (20 - 2 por cada incumplimiento)</label>
                            </div>
                            <div class="mt-2">
                                <label class="form-label small">Cantidad de incumplimientos</label>
                                <input type="number" min="0" name="delivery_breaches" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">2) Calidad del producto o servicio (40%)</label>
                        <select name="quality" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="meets">Cumple con los requisitos (40)</option>
                            <option value="not_meets">No cumple (0)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">3) Servicio postventa oportuno / garantías (10%)</label>
                        <select name="after_sales" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="full">Cumple oportunamente con todas las garantías y soporte técnico (10)</option>
                            <option value="partial">Cumple parcialmente con las garantías y soporte técnico (5)</option>
                            <option value="none">No cumple con las garantías ni brinda soluciones (0)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">4) Atención oportuna a SQR (10%)</label>
                        <select name="sqr" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="no_claims">No se han presentado quejas, atiende oportunamente las solicitudes (10)</option>
                            <option value="timely">Atiende oportunamente los reclamos (1 a 5 días) (5)</option>
                            <option value="untimely">No atiende reclamos oportunamente (0)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">5) Cumplimiento documental (20%)</label>
                        <select name="documents" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="complete">Cumple con todos los documentos solicitados (20)</option>
                            <option value="incomplete">No envía documentos completos o demora (0)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observations" class="form-control" rows="3" maxlength="1000" placeholder="Comentarios generales de la evaluación"></textarea>
                    </div>

                    <div class="col-12 text-end">
                        <button class="btn btn-primary"><i class="bi bi-send-check"></i> Guardar y notificar proveedor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h5 class="mb-3">Consulta histórica</h5>
                <form method="get" action="<?= htmlspecialchars(route_to('supplier_evaluations')) ?>" class="row g-2 align-items-end">
                    <input type="hidden" name="page" value="supplier_evaluations">
                    <div class="col-md-5">
                        <label class="form-label">Proveedor</label>
                        <select name="supplier_id" class="form-select">
                            <option value="0">Todos</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= (int)$supplier['id'] ?>" <?= ((int)$supplier['id'] === (int)$supplierId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
                    </div>
                    <div class="col-md-1 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive" style="max-height: 540px;">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Líder evaluador</th>
                                <th class="text-end">Puntaje</th>
                                <th>Estado</th>
                                <th>PDF</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $evaluation): ?>
                                <tr>
                                    <td>
                                        <a href="<?= htmlspecialchars(route_to('supplier_evaluations', array_merge($_GET, ['show' => (int)$evaluation['id']]))) ?>">
                                            <?= htmlspecialchars($evaluation['evaluation_date']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($evaluation['supplier_name']) ?></td>
                                    <td><?= htmlspecialchars($evaluation['evaluator_name']) ?></td>
                                    <td class="text-end fw-semibold"><?= (int)$evaluation['total_score'] ?></td>
                                    <td><span class="badge bg-secondary-subtle text-dark"><?= htmlspecialchars($evaluation['status_label']) ?></span></td>
                                    <td>
                                        <?php if (!empty($evaluation['pdf_path'])): ?>
                                            <a href="<?= htmlspecialchars(route_to('supplier_evaluation_pdf', ['evaluation_id' => (int)$evaluation['id']])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver PDF</a>
                                        <?php else: ?>
                                            <span class="text-muted small">N/D</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= htmlspecialchars(route_to('supplier_evaluations', array_merge($_GET, ['show' => (int)$evaluation['id'], 'edit' => (int)$evaluation['id']]))) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form method="post" action="<?= htmlspecialchars(route_to('supplier_evaluation_delete')) ?>" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta evaluación? Esta acción no se puede deshacer.');">
                                            <input type="hidden" name="id" value="<?= (int)$evaluation['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$evaluations): ?>
                                <tr><td colspan="7" class="text-center text-muted">Sin evaluaciones registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedEvaluation): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Resumen de evaluación #<?= (int)$selectedEvaluation['id'] ?></h5>
            <div class="row g-2 small">
                <div class="col-md-3"><strong>Proveedor:</strong> <?= htmlspecialchars($selectedEvaluation['supplier_name']) ?></div>
                <div class="col-md-3"><strong>NIT:</strong> <?= htmlspecialchars($selectedEvaluation['supplier_nit'] ?: 'N/D') ?></div>
                <div class="col-md-3"><strong>Servicio:</strong> <?= htmlspecialchars($selectedEvaluation['supplier_service'] ?: 'N/D') ?></div>
                <div class="col-md-3"><strong>Estado:</strong> <?= htmlspecialchars($selectedEvaluation['status_label']) ?></div>
            </div>
            <p class="mb-0 mt-2"><strong>Observaciones:</strong> <?= htmlspecialchars($selectedEvaluation['observations'] ?: 'Sin observaciones') ?></p>
            <?php if (!empty($selectedEvaluation['pdf_path'])): ?>
                <a href="<?= htmlspecialchars(route_to('supplier_evaluation_pdf', ['evaluation_id' => (int)$selectedEvaluation['id']])) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-3">
                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF de evaluación
                </a>
            <?php endif; ?>

            <?php
            $detailMap = [];
            foreach (($selectedEvaluation['details'] ?? []) as $detail) {
                $detailMap[$detail['criterion_code']] = $detail;
            }
            $deliveryMode = (($detailMap['delivery_time']['option_key'] ?? '') === 'breach') ? 'breach' : 'on_time';
            $deliveryBreaches = (int)($detailMap['delivery_time']['notes'] ?? 0);
            ?>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Corrección de evaluación</h6>
                <?php if (!$isEditMode): ?>
                    <a href="<?= htmlspecialchars(route_to('supplier_evaluations', array_merge($_GET, ['show' => (int)$selectedEvaluation['id'], 'edit' => (int)$selectedEvaluation['id']]))) ?>" class="btn btn-sm btn-outline-primary">Editar evaluación</a>
                <?php endif; ?>
            </div>
            <?php if ($isEditMode): ?>
            <form action="<?= htmlspecialchars(route_to('supplier_evaluation_update')) ?>" method="post" class="row g-2">
                <input type="hidden" name="id" value="<?= (int)$selectedEvaluation['id'] ?>">
                <div class="col-md-6">
                    <label class="form-label">Cumple con tiempos (20%)</label>
                    <select name="delivery_mode" class="form-select">
                        <option value="on_time" <?= $deliveryMode === 'on_time' ? 'selected' : '' ?>>A tiempo</option>
                        <option value="breach" <?= $deliveryMode === 'breach' ? 'selected' : '' ?>>Incumplimiento</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">N° incumplimientos</label>
                    <input type="number" min="0" name="delivery_breaches" class="form-control" value="<?= $deliveryBreaches ?>">
                </div>
                <div class="col-md-6"><label class="form-label">Calidad</label>
                    <select name="quality" class="form-select" required>
                        <option value="meets" <?= (($detailMap['quality']['option_key'] ?? '') === 'meets') ? 'selected' : '' ?>>Cumple</option>
                        <option value="not_meets" <?= (($detailMap['quality']['option_key'] ?? '') === 'not_meets') ? 'selected' : '' ?>>No cumple</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Postventa</label>
                    <select name="after_sales" class="form-select" required>
                        <option value="full" <?= (($detailMap['after_sales']['option_key'] ?? '') === 'full') ? 'selected' : '' ?>>Cumple oportunamente</option>
                        <option value="partial" <?= (($detailMap['after_sales']['option_key'] ?? '') === 'partial') ? 'selected' : '' ?>>Cumple parcialmente</option>
                        <option value="none" <?= (($detailMap['after_sales']['option_key'] ?? '') === 'none') ? 'selected' : '' ?>>No cumple</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">SQR</label>
                    <select name="sqr" class="form-select" required>
                        <option value="no_claims" <?= (($detailMap['sqr']['option_key'] ?? '') === 'no_claims') ? 'selected' : '' ?>>Sin quejas</option>
                        <option value="timely" <?= (($detailMap['sqr']['option_key'] ?? '') === 'timely') ? 'selected' : '' ?>>Atiende oportunamente</option>
                        <option value="untimely" <?= (($detailMap['sqr']['option_key'] ?? '') === 'untimely') ? 'selected' : '' ?>>No atiende oportunamente</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Documentación</label>
                    <select name="documents" class="form-select" required>
                        <option value="complete" <?= (($detailMap['documents']['option_key'] ?? '') === 'complete') ? 'selected' : '' ?>>Completa</option>
                        <option value="incomplete" <?= (($detailMap['documents']['option_key'] ?? '') === 'incomplete') ? 'selected' : '' ?>>Incompleta</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Observaciones</label>
                    <textarea name="observations" class="form-control" rows="3" maxlength="1000"><?= htmlspecialchars($selectedEvaluation['observations'] ?? '') ?></textarea>
                </div>
                <div class="col-12 text-end"><button class="btn btn-warning">Guardar corrección</button></div>
            </form>
            <p class="mb-0 mt-2 text-muted">La evaluación puede editarse para corregir puntajes, manteniendo trazabilidad en auditoría.</p>
            <?php else: ?>
                <p class="mb-0 text-muted">Los puntajes ya guardados no se editan desde el resumen. Usa el botón <strong>Editar evaluación</strong> para corregir y recalcular la evaluación del proveedor.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
