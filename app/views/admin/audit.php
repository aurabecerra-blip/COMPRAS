<?php include __DIR__ . '/../layout/header.php'; ?>
<h2>Auditoría</h2>
<table class="table table-sm table-striped">
    <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= $log['created_at'] ?></td>
            <td><?= htmlspecialchars($log['email'] ?? 'sistema') ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><code><?= htmlspecialchars($log['detail_json']) ?></code></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
