<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="mb-1">Seguimiento de solicitud</h4>
                        <p class="mb-0 text-muted">Valida el ciclo de la solicitud con su código de seguimiento.</p>
                    </div>
                    <span class="badge text-bg-primary">ISO 9001 - Trazabilidad</span>
                </div>
                <form class="row g-2" method="get" action="<?= htmlspecialchars(route_to('track')) ?>">
                    <div class="col-md-9">
                        <label class="form-label small text-muted">Código de seguimiento</label>
                        <input type="text" name="code" class="form-control" placeholder="Ej: PR-ABC123" value="<?= htmlspecialchars($code) ?>" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search"></i> Consultar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($code && !$pr): ?>
            <div class="alert alert-warning shadow-sm">No encontramos una solicitud con el código proporcionado.</div>
        <?php endif; ?>

        <?php if ($pr): ?>
            <?php
            $statuses = [
                'BORRADOR' => ['label' => 'Borrador', 'icon' => 'bi-pencil', 'color' => 'secondary'],
                'ENVIADA' => ['label' => 'Enviada', 'icon' => 'bi-send', 'color' => 'info'],
                'APROBADA' => ['label' => 'Aprobada', 'icon' => 'bi-check-circle', 'color' => 'success'],
                'RECHAZADA' => ['label' => 'Rechazada', 'icon' => 'bi-x-circle', 'color' => 'danger'],
                'CANCELADA' => ['label' => 'Cancelada', 'icon' => 'bi-slash-circle', 'color' => 'dark'],
            ];
            $current = $statuses[$pr['status']] ?? $statuses['BORRADOR'];
            $steps = ['BORRADOR', 'ENVIADA', 'APROBADA'];
            ?>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-dark-subtle text-dark fw-semibold"><?= htmlspecialchars($pr['tracking_code']) ?></span>
                                <span class="badge bg-<?= htmlspecialchars($current['color']) ?>"><?= htmlspecialchars($current['label']) ?></span>
                            </div>
                            <h5 class="mb-0"><?= htmlspecialchars($pr['title']) ?></h5>
                            <small class="text-muted">Creada: <?= htmlspecialchars($pr['created_at']) ?> · Última actualización: <?= htmlspecialchars($pr['updated_at']) ?></small>
                        </div>
                        <div class="text-end">
                            <p class="text-muted mb-1 small">Área</p>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($pr['area']) ?></p>
                        </div>
                    </div>

                    <div class="timeline mb-4">
                        <?php foreach ($steps as $idx => $step): ?>
                            <?php
                            $stepStatus = $statuses[$step];
                            $completed = array_search($pr['status'], $steps, true) >= $idx || in_array($pr['status'], ['RECHAZADA', 'CANCELADA'], true);
                            $active = $pr['status'] === $step;
                            ?>
                            <div class="timeline-step <?= $completed ? 'completed' : '' ?> <?= $active ? 'active' : '' ?>">
                                <div class="timeline-icon bg-<?= htmlspecialchars($stepStatus['color']) ?>">
                                    <i class="bi <?= htmlspecialchars($stepStatus['icon']) ?>"></i>
                                </div>
                                <div class="timeline-label"><?= htmlspecialchars($stepStatus['label']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($pr['rejection_reason'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                            <div>
                                <strong>Rechazada</strong>
                                <p class="mb-0"><?= htmlspecialchars($pr['rejection_reason']) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="card h-100 border-0 bg-body-tertiary">
                                <div class="card-body">
                                    <h6 class="text-muted text-uppercase mb-2">Justificación</h6>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($pr['justification'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="card h-100 border-0">
                                <div class="card-body">
                                    <h6 class="text-muted text-uppercase mb-2">Detalle</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2"><i class="bi bi-diagram-3 text-primary"></i> Área: <strong><?= htmlspecialchars($pr['area']) ?></strong></li>
                                        <li class="mb-0"><i class="bi bi-file-earmark-text text-secondary"></i> Descripción: <strong><?= $pr['description'] ? htmlspecialchars($pr['description']) : 'N/A' ?></strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-muted text-uppercase mb-2">Ítems solicitados</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr><th>#</th><th>Descripción</th><th class="text-end">Cantidad</th><th class="text-end">Precio Unit.</th><th class="text-end">Subtotal</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pr['items'] as $idx => $item): ?>
                                        <tr>
                                            <td class="text-muted"><?= $idx + 1 ?></td>
                                            <td><?= htmlspecialchars($item['description']) ?></td>
                                            <td class="text-end"><?= number_format((float)$item['quantity'], 2) ?></td>
                                            <td class="text-end"><?= number_format((float)$item['unit_price'], 2) ?></td>
                                            <td class="text-end fw-semibold"><?= number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
