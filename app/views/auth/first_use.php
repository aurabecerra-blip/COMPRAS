<?php
$settingsRepo = $settingsRepo ?? ($GLOBALS['settingsRepo'] ?? null);
$flash = $flash ?? ($GLOBALS['flash'] ?? null);
$brandName = $settingsRepo ? $settingsRepo->get('company_name', 'AOS') : 'AOS';
$brandLogoSetting = $settingsRepo ? $settingsRepo->get('brand_logo_path', 'assets/aos-logo.svg') : 'assets/aos-logo.svg';
$brandLogo = str_starts_with($brandLogoSetting, 'http') ? $brandLogoSetting : asset_url($brandLogoSetting);
$flashMessages = $flash ? $flash->getAll() : [];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Primer uso - <?= htmlspecialchars($brandName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?= htmlspecialchars($brandLogo) ?>" alt="Logo" style="height:48px;">
                        <h5 class="mt-2">Configuración inicial</h5>
                        <p class="mb-0 text-muted">Crea el primer administrador activo.</p>
                    </div>
                    <?php foreach ($flashMessages as $msg): ?>
                        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['message']) ?></div>
                    <?php endforeach; ?>
                    <form method="post" action="<?= htmlspecialchars(route_to('do_first_use')) ?>">
                        <div class="mb-2">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email corporativo</label>
                            <input type="email" name="email" class="form-control" pattern="^[^@\s]+@aossas\.com$" required>
                            <small class="text-muted">Debe terminar en @aossas.com</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Crear administrador</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
