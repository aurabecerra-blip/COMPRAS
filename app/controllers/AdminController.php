<?php
class AdminController
{
    public function __construct(private SettingsRepository $settings, private UserRepository $users, private Flash $flash, private AuditLogger $audit, private Auth $auth)
    {
    }

    public function index(): void
    {
        $this->auth->requireRole(['admin']);
        $settings = $this->settings->all();
        $users = $this->users->all();
        $settingsRepo = $this->settings;
        include __DIR__ . '/../views/admin/index.php';
    }

    public function updateSettings(): void
    {
        $this->auth->requireRole(['admin']);
        $company = trim($_POST['company_name'] ?? '');
        $logo = trim($_POST['brand_logo_path'] ?? '');
        $this->settings->set('company_name', $company ?: 'AOS');
        $this->settings->set('brand_logo_path', $logo ?: '/public/assets/aos-logo.svg');
        $this->audit->log($this->auth->user()['id'], 'settings_update', ['company' => $company, 'logo' => $logo]);
        $this->flash->add('success', 'Configuración guardada');
        header('Location: /index.php?page=admin');
    }

    public function storeUser(): void
    {
        $this->auth->requireRole(['admin']);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? '',
            'password' => $_POST['password'] ?? '',
        ];
        if ($data['name'] === '' || $data['email'] === '' || $data['password'] === '') {
            $this->flash->add('danger', 'Todos los campos son obligatorios');
            header('Location: /index.php?page=admin');
            return;
        }
        $this->users->create($data);
        $this->audit->log($this->auth->user()['id'], 'user_create', ['email' => $data['email'], 'role' => $data['role']]);
        $this->flash->add('success', 'Usuario creado');
        header('Location: /index.php?page=admin');
    }
}
