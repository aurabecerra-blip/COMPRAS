<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$statusBadges = [
    'BORRADOR' => 'secondary',
    'ENVIADA' => 'info',
    'APROBADA' => 'success',
    'RECHAZADA' => 'danger',
    'CANCELADA' => 'dark',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <p class="text-uppercase text-muted small mb-1">Ciclo ISO 9001</p>
        <h3 class="mb-0">Solicitudes de Compra</h3>
        <p class="mb-0 text-muted">Trazabilidad, aprobación y transición a órdenes de compra.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars(route_to('track')) ?>" class="btn btn-light border"><i class="bi bi-qr-code"></i> Seguimiento</a>
        <a href="<?= htmlspecialchars(route_to('purchase_request_create')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva PR</a>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>Código</th><th>Título</th><th>Estado</th><th>Solicitante</th><th>Creación</th><th class="text-end">Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td class="text-muted">#<?= $r['id'] ?></td>
                            <td><span class="badge bg-dark-subtle text-dark"><?= htmlspecialchars($r['tracking_code'] ?? 'N/A') ?></span></td>
                            <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                            <td><span class="badge bg-<?= $statusBadges[$r['status']] ?? 'secondary' ?>"><?= $r['status'] ?></span></td>
                            <td><?= htmlspecialchars($r['requester_name']) ?></td>
                            <td><?= $r['created_at'] ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(route_to('purchase_request_edit', ['id' => $r['id']])) ?>"><i class="bi bi-pencil-square"></i></a>
                                    <a class="btn btn-outline-primary" href="<?= htmlspecialchars(route_to('quotations', ['id' => $r['id']])) ?>"><i class="bi bi-card-checklist"></i></a>
                                    <a class="btn btn-outline-success" href="<?= htmlspecialchars(route_to('provider_selection', ['id' => $r['id']])) ?>" title="Cotizaciones y Selección de Proveedor"><i class="bi bi-clipboard2-check"></i></a>
                                    <a class="btn btn-outline-primary" href="<?= htmlspecialchars(route_to('supplier_selection', ['id' => $r['id']])) ?>" title="Módulo B: Selección de proveedor"><i class="bi bi-diagram-3"></i></a>
                                    <a class="btn btn-outline-dark" href="<?= htmlspecialchars(route_to('track', ['code' => $r['tracking_code'] ?? ''])) ?>"><i class="bi bi-qr-code"></i></a>
                                </div>
                                <?php if ($r['status'] === 'BORRADOR'): ?>
                                    <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_send')) ?>" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-sm btn-warning mt-2" type="submit"><i class="bi bi-send"></i> Enviar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($r['status'] === 'ENVIADA' && in_array($auth->user()['role'], ['aprobador','administrador'], true)): ?>
                                    <div class="d-flex flex-column gap-1 mt-2">
                                        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_approve')) ?>" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check2"></i> Aprobar</button>
                                        </form>
                                        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_reject')) ?>" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Motivo" required>
                                            <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-x"></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
