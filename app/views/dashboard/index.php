<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$role = $authUser['role'] ?? '';
$generalCards = [
    ['title' => 'Solicitudes registradas', 'value' => $stats['prs'], 'subtitle' => 'Ciclo completo de compras', 'variant' => 'primary'],
    ['title' => 'Cotizaciones cargadas', 'value' => $stats['quotations'], 'subtitle' => 'Comparativas recibidas', 'variant' => 'info'],
    ['title' => 'OC abiertas', 'value' => $stats['pos_open'], 'subtitle' => 'Pendientes de recepción o facturación', 'variant' => 'success'],
    ['title' => 'Facturas registradas', 'value' => $stats['invoices'], 'subtitle' => 'Documentos contables en el sistema', 'variant' => 'warning'],
];

$roleCards = match ($role) {
    'requester' => [
        ['title' => 'Borradores en curso', 'value' => $stats['draft_prs'], 'subtitle' => 'PR listas para completar', 'variant' => 'secondary'],
        ['title' => 'En revisión', 'value' => $stats['approval_prs'], 'subtitle' => 'Esperando aprobación', 'variant' => 'info'],
        ['title' => 'Rechazadas', 'value' => $stats['rejected_prs'], 'subtitle' => 'Requieren ajustes', 'variant' => 'danger'],
    ],
    'approver' => [
        ['title' => 'Pendientes de aprobar', 'value' => $stats['approval_prs'], 'subtitle' => 'PR en tu cola', 'variant' => 'primary'],
        ['title' => 'Aprobadas', 'value' => $stats['approved_prs'], 'subtitle' => 'Listas para compras', 'variant' => 'success'],
    ],
    'buyer' => [
        ['title' => 'PR aprobadas', 'value' => $stats['approved_prs'], 'subtitle' => 'Listas para negociar', 'variant' => 'primary'],
        ['title' => 'Cotizaciones', 'value' => $stats['quotations'], 'subtitle' => 'Ofertas cargadas', 'variant' => 'info'],
        ['title' => 'OC abiertas', 'value' => $stats['pos_open'], 'subtitle' => 'Seguimiento a proveedores', 'variant' => 'success'],
    ],
    'receiver' => [
        ['title' => 'OC abiertas', 'value' => $stats['pos_open'], 'subtitle' => 'Pendientes de recepción', 'variant' => 'primary'],
        ['title' => 'Recepciones registradas', 'value' => $stats['receipts'], 'subtitle' => 'Movimientos de almacén', 'variant' => 'success'],
    ],
    'accountant' => [
        ['title' => 'Facturas registradas', 'value' => $stats['invoices'], 'subtitle' => 'Documentos listos para pago', 'variant' => 'warning'],
        ['title' => 'OC cerradas', 'value' => $stats['pos_closed'], 'subtitle' => 'Conciliadas y terminadas', 'variant' => 'secondary'],
    ],
    'admin' => [
        ['title' => 'Usuarios activos', 'value' => $stats['users'], 'subtitle' => 'Control y roles asignados', 'variant' => 'primary'],
        ['title' => 'Proveedores cargados', 'value' => $stats['suppliers'], 'subtitle' => 'Catálogo de abastecimiento', 'variant' => 'success'],
        ['title' => 'Órdenes totales', 'value' => $stats['pos'], 'subtitle' => 'Historico de compras', 'variant' => 'info'],
    ],
    default => [],
};
?>

<div class="row g-3 mb-4">
    <?php foreach ($generalCards as $card): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0 bg-<?= $card['variant'] ?> text-white">
                <div class="card-body">
                    <p class="mb-1 text-uppercase small fw-semibold"><?= htmlspecialchars($card['title']) ?></p>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <span class="display-5 fw-bold"><?= number_format($card['value']) ?></span>
                        <span class="opacity-75"><?= htmlspecialchars($card['subtitle']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="card-title mb-1">KPIs por rol</h5>
                <p class="text-muted mb-0">Indicadores accionables según tu responsabilidad.</p>
            </div>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($role ?: 'usuario') ?></span>
        </div>
        <div class="row g-3">
            <?php foreach ($roleCards as $card): ?>
                <div class="col-12 col-md-4 col-xl-3">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="mb-0 text-uppercase small fw-semibold text-<?= $card['variant'] ?>"><?= htmlspecialchars($card['title']) ?></p>
                                <span class="badge bg-<?= $card['variant'] ?>"><?= number_format($card['value']) ?></span>
                            </div>
                            <p class="mb-0 text-muted"><?= htmlspecialchars($card['subtitle']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($roleCards)): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">No hay KPIs configurados para tu rol.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
