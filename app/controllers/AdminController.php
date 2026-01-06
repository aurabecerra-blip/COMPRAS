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
        $areas = trim($_POST['form_areas'] ?? '');
        $costCenters = trim($_POST['form_cost_centers'] ?? '');
        $notificationRecipients = trim($_POST['notification_recipients'] ?? '');

        $config = require __DIR__ . '/../config/config.php';
        if (!empty($_FILES['brand_logo_file']['name'])) {
            $file = $_FILES['brand_logo_file'];
            $allowed = ['image/png', 'image/jpeg', 'image/svg+xml'];
            if (in_array($file['type'], $allowed, true)) {
                $uploadDir = rtrim($config['upload_dir'], '/') . '/branding';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $destName = uniqid('logo_') . '_' . basename($file['name']);
                $dest = $uploadDir . '/' . $destName;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $logo = 'uploads/branding/' . $destName;
                }
            }
        }
        $this->settings->set('company_name', $company ?: 'AOS');
        $this->settings->set('brand_logo_path', $logo ?: 'assets/aos-logo.svg');
        $this->settings->set('brand_primary_color', $primary ?: '#0d6efd');
        $this->settings->set('brand_accent_color', $accent ?: '#198754');
        $this->settings->set('form_areas', $areas);
        $this->settings->set('form_cost_centers', $costCenters);
        $this->settings->set('notification_recipients', $notificationRecipients);
        $this->audit->log($this->auth->user()['id'], 'settings_update', [
            'company' => $company,
            'logo' => $logo,
            'primary' => $primary,
            'accent' => $accent,
            'form_areas' => $areas,
            'form_cost_centers' => $costCenters,
            'notification_recipients' => $notificationRecipients,
        ]);
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
