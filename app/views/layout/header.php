<?php
$settingsRepo = $settingsRepo ?? ($GLOBALS['settingsRepo'] ?? null);
$auth = $auth ?? ($GLOBALS['auth'] ?? null);
$flash = $flash ?? ($GLOBALS['flash'] ?? null);

$brandName = $settingsRepo ? $settingsRepo->get('company_name', 'AOS') : 'AOS';
$brandLogoSetting = $settingsRepo ? $settingsRepo->get('brand_logo_path', 'assets/aos-logo.svg') : 'assets/aos-logo.svg';
$brandLogo = str_starts_with($brandLogoSetting, 'http') ? $brandLogoSetting : asset_url($brandLogoSetting);
$brandPrimary = $settingsRepo ? $settingsRepo->get('brand_primary_color', '#0d6efd') : '#0d6efd';
$brandAccent = $settingsRepo ? $settingsRepo->get('brand_accent_color', '#198754') : '#198754';
$authUser = $auth ? $auth->user() : null;
$flashMessages = $flash ? $flash->getAll() : [];
$currentPage = $_GET['page'] ?? ($authUser ? 'dashboard' : 'login');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compras - <?= htmlspecialchars($brandName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: <?= htmlspecialchars($brandPrimary) ?>;
            --brand-accent: <?= htmlspecialchars($brandAccent) ?>;
            --surface: #f8f9fb;
            --text-muted: #6c757d;
            --sidebar-width: 260px;
        }
        body { background-color: var(--surface); }
        .btn-primary { background-color: var(--brand-primary); border-color: var(--brand-primary); }
        .btn-outline-primary { color: var(--brand-primary); border-color: var(--brand-primary); }
        .btn-outline-primary:hover { background-color: var(--brand-primary); color: #fff; }
        .btn-success { background-color: var(--brand-accent); border-color: var(--brand-accent); }
        .text-primary { color: var(--brand-primary) !important; }
        .bg-primary, .navbar { background-color: var(--brand-primary) !important; }

        .app-shell { min-height: 100vh; }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--brand-primary), #0b1f33);
            color: #fff;
            position: sticky;
            top: 0;
        }
        .sidebar .brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar .brand img { height: 32px; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .sidebar .nav-link .icon { width: 1.2rem; text-align: center; }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }
        .sidebar .section-label {
            padding: 0.75rem 1.5rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-size: .75rem;
            color: rgba(255,255,255,0.6);
        }
        .app-main { background: var(--surface); }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #eaecef;
            padding: 0.9rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--brand-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .app-content { padding: 1.5rem; }
        .card { border: 1px solid #edf0f4; }
        .badge-soft {
            background: rgba(13,110,253,0.1);
            color: var(--brand-primary);
        }
        .timeline {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .timeline-step {
            text-align: center;
            position: relative;
        }
        .timeline-step:before {
            content: '';
            position: absolute;
            top: 14px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: #dce1e7;
            z-index: 0;
        }
        .timeline-step:first-child:before { display: none; }
        .timeline-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            position: relative;
            z-index: 1;
        }
        .timeline-step.completed:before { background: var(--brand-primary); }
        .timeline-step.completed .timeline-icon { box-shadow: 0 0 0 4px rgba(13,110,253,0.15); }
        .timeline-label { margin-top: 0.35rem; font-size: 0.9rem; color: #4a5568; }
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                inset: 0 auto 0 -100%;
                transition: all .3s ease;
                z-index: 20;
            }
            body.sidebar-open .sidebar { left: 0; }
            body.sidebar-open .sidebar-backdrop { display: block; }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.4);
                z-index: 15;
                display: none;
            }
        }
    </style>
</head>
<body>
<?php $role = $authUser['role'] ?? ''; ?>
<?php
$navItems = [
    ['label' => 'Dashboard', 'route' => route_to('dashboard'), 'icon' => 'bi-speedometer2', 'visible' => true],
    ['label' => 'Solicitudes', 'route' => route_to('purchase_requests'), 'icon' => 'bi-list-check', 'visible' => in_array($role, ['solicitante', 'aprobador', 'compras', 'administrador'], true)],
    ['label' => 'Cotizaciones', 'route' => route_to('purchase_requests'), 'icon' => 'bi-card-checklist', 'visible' => in_array($role, ['compras', 'administrador'], true)],
    ['label' => 'Órdenes de compra', 'route' => route_to('purchase_orders'), 'icon' => 'bi-receipt', 'visible' => in_array($role, ['compras', 'recepcion', 'administrador'], true)],
    ['label' => 'Proveedores', 'route' => route_to('suppliers'), 'icon' => 'bi-people', 'visible' => in_array($role, ['compras', 'administrador'], true)],
    ['label' => 'Evaluación proveedores', 'route' => route_to('supplier_evaluations'), 'icon' => 'bi-clipboard-check', 'visible' => in_array($role, ['lider', 'administrador'], true)],
    ['label' => 'Selección proveedores', 'route' => route_to('purchase_requests'), 'icon' => 'bi-diagram-3', 'visible' => in_array($role, ['compras', 'lider', 'administrador'], true)],
    ['label' => 'Auditoría', 'route' => route_to('audit'), 'icon' => 'bi-clipboard-data', 'visible' => in_array($role, ['administrador'], true)],
    ['label' => 'Configuración', 'route' => route_to('admin'), 'icon' => 'bi-gear', 'visible' => in_array($role, ['administrador'], true)],
    ['label' => 'Usuarios', 'route' => route_to('admin_users'), 'icon' => 'bi-people-fill', 'visible' => in_array($role, ['administrador'], true)],
    ['label' => 'Seguimiento', 'route' => route_to('track'), 'icon' => 'bi-qr-code', 'visible' => true],
];
?>
<?php if ($authUser): ?>
    <div class="sidebar-backdrop"></div>
    <div class="app-shell d-flex">
        <aside class="sidebar">
            <div class="brand d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>">
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($brandName) ?></div>
                    <small class="text-white-50">Compras & Gestión</small>
                </div>
            </div>
            <div class="section-label">Navegación</div>
            <nav class="nav flex-column">
                <?php foreach ($navItems as $item): ?>
                    <?php if (!$item['visible']) { continue; } ?>
                    <a class="nav-link <?= strpos($item['route'], 'page=' . $currentPage) !== false ? 'active' : '' ?>" href="<?= htmlspecialchars($item['route']) ?>">
                        <span class="icon"><i class="bi <?= htmlspecialchars($item['icon']) ?>"></i></span>
                        <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <div class="app-main flex-grow-1">
            <header class="topbar d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-primary d-lg-none btn-toggle-sidebar" type="button">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <div class="fw-semibold">Panel de compras</div>
                        <small class="text-muted">Flujo ISO 9001 · Solicitud → OC → Recepción</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-dark text-uppercase"><?= htmlspecialchars($role ?: 'usuario') ?></span>
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-circle"><?= strtoupper(substr($authUser['name'] ?? $authUser['email'] ?? 'U', 0, 2)) ?></div>
                        <div class="text-end">
                            <div class="fw-semibold small"><?= htmlspecialchars($authUser['name'] ?? $authUser['email'] ?? '') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($authUser['email'] ?? '') ?></div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars(route_to('logout')) ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </header>
            <div class="app-content container-fluid">
                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?> shadow-sm"><?= htmlspecialchars($msg['message']) ?></div>
                <?php endforeach; ?>
<?php else: ?>
<div class="container py-4">
<?php endif; ?>
