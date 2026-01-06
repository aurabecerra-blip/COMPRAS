<?php
$settingsRepo = $settingsRepo ?? ($GLOBALS['settingsRepo'] ?? null);
$brandName = $settingsRepo ? $settingsRepo->get('company_name', 'AOS') : 'AOS';
$brandLogo = $settingsRepo ? $settingsRepo->get('brand_logo_path', '/public/assets/aos-logo.svg') : '/public/assets/aos-logo.svg';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compras - <?= htmlspecialchars($brandName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php $role = $auth->user()['role'] ?? ''; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">
            <img src="<?= htmlspecialchars($brandLogo) ?>" alt="AOS" style="height:32px" class="me-2 align-text-top">
            <?= htmlspecialchars($brandName) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/index.php">Dashboard</a></li>
                <?php if (in_array($role, ['requester','approver','buyer','receiver','accountant','admin'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="/index.php?page=purchase_requests">Solicitudes</a></li>
                <?php endif; ?>
                <?php if (in_array($role, ['buyer','admin'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="/index.php?page=suppliers">Proveedores</a></li>
                <?php endif; ?>
                <?php if (in_array($role, ['admin','accountant'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="/index.php?page=audit">Auditoría</a></li>
                <?php endif; ?>
                <?php if ($role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="/index.php?page=admin">Administración</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text me-3"><?= htmlspecialchars($auth->user()['email'] ?? '') ?> (<?= htmlspecialchars($auth->user()['role'] ?? '') ?>)</span>
            <a href="/index.php?page=logout" class="btn btn-outline-light">Salir</a>
        </div>
    </div>
</nav>
<div class="container">
    <?php foreach (($flash->getAll()) as $msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['message']) ?></div>
    <?php endforeach; ?>
