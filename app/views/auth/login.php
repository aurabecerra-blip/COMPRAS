<?php
$settingsRepo = $settingsRepo ?? ($GLOBALS['settingsRepo'] ?? null);
$brandName = $settingsRepo ? $settingsRepo->get('company_name', 'AOS') : 'AOS';
$brandLogoSetting = $settingsRepo ? $settingsRepo->get('brand_logo_path', asset_url('/assets/aos-logo.svg')) : asset_url('/assets/aos-logo.svg');
$brandLogo = str_starts_with($brandLogoSetting, 'http') ? $brandLogoSetting : asset_url($brandLogoSetting);
$brandPrimary = $settingsRepo ? $settingsRepo->get('brand_primary_color', '#0d6efd') : '#0d6efd';
$brandAccent = $settingsRepo ? $settingsRepo->get('brand_accent_color', '#198754') : '#198754';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Compras <?= htmlspecialchars($brandName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: <?= htmlspecialchars($brandPrimary) ?>;
            --brand-accent: <?= htmlspecialchars($brandAccent) ?>;
        }
        .btn-primary {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        .text-primary {
            color: var(--brand-primary) !important;
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?= htmlspecialchars($brandLogo) ?>" alt="AOS" style="height:48px;">
                        <h5 class="mt-2 text-primary"><?= htmlspecialchars($brandName) ?></h5>
                        <p class="mb-0 text-muted">Control de compras</p>
                    </div>
                    <?php foreach (($flash->getAll()) as $msg): ?>
                        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['message']) ?></div>
                    <?php endforeach; ?>
                    <form method="post" action="<?= htmlspecialchars(route_to('login_submit')) ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
