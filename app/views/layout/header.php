<?php
$settingsRepo = $settingsRepo ?? ($GLOBALS['settingsRepo'] ?? null);
$auth = $auth ?? ($GLOBALS['auth'] ?? null);
$flash = $flash ?? ($GLOBALS['flash'] ?? null);

$brandName = $settingsRepo ? $settingsRepo->get('company_name', 'AOS') : 'AOS';
$brandLogoSetting = $settingsRepo ? $settingsRepo->get('brand_logo_path', asset_url('/assets/aos-logo.svg')) : asset_url('/assets/aos-logo.svg');
$brandLogo = str_starts_with($brandLogoSetting, 'http') ? $brandLogoSetting : asset_url($brandLogoSetting);
$brandPrimary = $settingsRepo ? $settingsRepo->get('brand_primary_color', '#0d6efd') : '#0d6efd';
$brandAccent = $settingsRepo ? $settingsRepo->get('brand_accent_color', '#198754') : '#198754';
$authUser = $auth ? $auth->user() : null;
$flashMessages = $flash ? $flash->getAll() : [];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compras - <?= htmlspecialchars($brandName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: <?= htmlspecialchars($brandPrimary) ?>;
            --brand-accent: <?= htmlspecialchars($brandAccent) ?>;
        }
        .navbar, .bg-primary {
            background-color: var(--brand-primary) !important;
        }
        .btn-primary {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        .btn-outline-primary {
            color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        .btn-outline-primary:hover {
            background-color: var(--brand-primary);
            color: #fff;
        }
        .text-primary {
            color: var(--brand-primary) !important;
        }
        .bg-success {
            background-color: var(--brand-accent) !important;
        }
        .btn-success {
            background-color: var(--brand-accent);
            border-color: var(--brand-accent);
        }
    </style>
</head>
<body>
<?php $role = $authUser['role'] ?? ''; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= htmlspecialchars(route_to('dashboard')) ?>">
            <img src="<?= htmlspecialchars($brandLogo) ?>" alt="AOS" style="height:32px" class="me-2 align-text-top">
            <?= htmlspecialchars($brandName) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(route_to('dashboard')) ?>">Dashboard</a></li>
                <?php if (in_array($role, ['solicitante','aprobador','compras','recepcion','administrador'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(route_to('purchase_requests')) ?>">Solicitudes</a></li>
                <?php endif; ?>
                <?php if (in_array($role, ['compras','administrador'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(route_to('suppliers')) ?>">Proveedores</a></li>
                <?php endif; ?>
                <?php if (in_array($role, ['administrador'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(route_to('audit')) ?>">Auditoría</a></li>
                <?php endif; ?>
                <?php if ($role === 'administrador'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(route_to('admin')) ?>">Administración</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text me-3"><?= htmlspecialchars($authUser['email'] ?? '') ?> (<?= htmlspecialchars($authUser['role'] ?? '') ?>)</span>
            <a href="<?= htmlspecialchars(route_to('logout')) ?>" class="btn btn-outline-light">Salir</a>
        </div>
    </div>
</nav>
<div class="container">
    <?php foreach ($flashMessages as $msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['message']) ?></div>
    <?php endforeach; ?>
