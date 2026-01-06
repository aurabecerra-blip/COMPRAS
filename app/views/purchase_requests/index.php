<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
    <h2>Solicitudes de Compra</h2>
    <a href="<?= htmlspecialchars(route_to('purchase_request_create')) ?>" class="btn btn-primary">Nueva PR</a>
</div>
<table class="table table-striped">
    <thead>
        <tr><th>ID</th><th>Título</th><th>Estado</th><th>Solicitante</th><th>Creación</th><th>Acciones</th></tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $r): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><span class="badge bg-secondary"><?= $r['status'] ?></span></td>
                <td><?= htmlspecialchars($r['requester_name']) ?></td>
                <td><?= $r['created_at'] ?></td>
                <td class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(route_to('purchase_request_edit', ['id' => $r['id']])) ?>">Ver/Editar</a>
                    <?php if ($r['status'] === 'BORRADOR'): ?>
                        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_send')) ?>" class="d-inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-warning" type="submit">Enviar</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'ENVIADA' && in_array($auth->user()['role'], ['approver','admin'], true)): ?>
                        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_start_approval')) ?>" class="d-inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-info" type="submit">Tomar en aprobación</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'EN_APROBACION' && in_array($auth->user()['role'], ['approver','admin'], true)): ?>
                        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_approve')) ?>" class="d-inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-success" type="submit">Aprobar</button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_reject')) ?>" class="d-inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Rechazar</button>
                        </form>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(route_to('quotations', ['id' => $r['id']])) ?>">Cotizaciones</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
