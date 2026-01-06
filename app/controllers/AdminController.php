<?php
class AdminController
{
    public function __construct(private SettingsRepository $settings, private UserRepository $users, private Flash $flash, private AuditLogger $audit, private Auth $auth, private AuthMiddleware $authMiddleware)
    {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $settings = $this->settings->all();
        $users = $this->users->all();
        $settingsRepo = $this->settings;
        include __DIR__ . '/../views/admin/index.php';
    }

    public function updateSettings(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $company = trim($_POST['company_name'] ?? '');
        $logo = trim($_POST['brand_logo_path'] ?? '');
        $primary = trim($_POST['brand_primary_color'] ?? '');
        $accent = trim($_POST['brand_accent_color'] ?? '');
        $this->settings->set('company_name', $company ?: 'AOS');
        $this->settings->set('brand_logo_path', $logo ?: asset_url('/assets/aos-logo.svg'));
        $this->settings->set('brand_primary_color', $primary ?: '#0d6efd');
        $this->settings->set('brand_accent_color', $accent ?: '#198754');
        $this->audit->log($this->auth->user()['id'], 'settings_update', ['company' => $company, 'logo' => $logo, 'primary' => $primary, 'accent' => $accent]);
        $this->flash->add('success', 'Configuración guardada');
        header('Location: ' . route_to('admin'));
    }

    public function storeUser(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? '',
            'password' => $_POST['password'] ?? '',
        ];
        if ($data['name'] === '' || $data['email'] === '' || $data['password'] === '') {
            $this->flash->add('danger', 'Todos los campos son obligatorios');
            header('Location: ' . route_to('admin'));
            return;
        }
        $this->users->create($data);
        $this->audit->log($this->auth->user()['id'], 'user_create', ['email' => $data['email'], 'role' => $data['role']]);
        $this->flash->add('success', 'Usuario creado');
        header('Location: ' . route_to('admin'));
    }
}
