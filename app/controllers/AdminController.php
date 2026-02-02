<?php
class AdminController
{
    public function __construct(
        private SettingsRepository $settings,
        private UserRepository $users,
        private NotificationTypeRepository $notificationTypes,
        private NotificationLogRepository $notificationLogs,
        private NotificationService $notifications,
        private Flash $flash,
        private AuditLogger $audit,
        private Auth $auth,
        private AuthMiddleware $authMiddleware
    )
    {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $settings = $this->settings->all();
        $users = $this->users->all();
        $notificationTypes = $this->notificationTypes->all();
        $notificationLogs = $this->notificationLogs->latest();
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
        $this->audit->log($this->auth->user()['id'], 'settings_update', [
            'company' => $company,
            'logo' => $logo,
            'primary' => $primary,
            'accent' => $accent,
            'form_areas' => $areas,
            'form_cost_centers' => $costCenters,
        ]);
        $this->flash->add('success', 'Configuración guardada');
        header('Location: ' . route_to('admin'));
    }

    public function updateNotifications(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);

        $payload = $this->persistNotificationSettings($_POST);

        $this->audit->log($this->auth->user()['id'], 'notifications_update', $payload);
        $this->flash->add('success', 'Configuración de notificaciones guardada');
        header('Location: ' . route_to('admin', ['tab' => 'notifications']));
    }

    public function sendTestNotification(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $payload = $this->persistNotificationSettings($_POST);
        $recipient = $payload['test_email'] ?? $this->settings->get('notifications_test_email', '');
        if ($recipient === '') {
            $this->flash->add('danger', 'Debes ingresar un correo de prueba');
            header('Location: ' . route_to('admin', ['tab' => 'notifications']));
            return;
        }
        $this->notifications->sendTestEmail($recipient);
        $this->flash->add('success', 'Correo de prueba enviado (revisa el log).');
        header('Location: ' . route_to('admin', ['tab' => 'notifications']));
    }

    public function updateNotificationTypes(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $types = $_POST['types'] ?? [];
        foreach ($types as $id => $typeData) {
            $active = isset($typeData['is_active']);
            $channel = $typeData['channel'] ?? 'email';
            $this->notificationTypes->update((int)$id, $active, $channel);
            $roles = $typeData['roles'] ?? [];
            $this->notificationTypes->setRoles((int)$id, $roles);
        }
        $this->flash->add('success', 'Tipos de notificación actualizados');
        header('Location: ' . route_to('admin', ['tab' => 'notifications']));
    }

    public function createNotificationType(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $code = trim($_POST['new_type_code'] ?? '');
        $name = trim($_POST['new_type_name'] ?? '');
        $description = trim($_POST['new_type_description'] ?? '');
        $channel = trim($_POST['new_type_channel'] ?? 'email');

        if ($code === '' || $name === '') {
            $this->flash->add('danger', 'Código y nombre son obligatorios.');
            header('Location: ' . route_to('admin', ['tab' => 'notifications']));
            return;
        }

        $this->notificationTypes->create($code, $name, $description, $channel);
        $this->flash->add('success', 'Tipo de notificación creado');
        header('Location: ' . route_to('admin', ['tab' => 'notifications']));
    }

    private function persistNotificationSettings(array $input): array
    {
        $enabled = isset($input['notifications_enabled']) ? '1' : '0';
        $emailEnabled = isset($input['notifications_email_enabled']) ? '1' : '0';
        $smtpHost = trim($input['notifications_smtp_host'] ?? '');
        $smtpPort = trim($input['notifications_smtp_port'] ?? '');
        $smtpSecurity = trim($input['notifications_smtp_security'] ?? 'none');
        $smtpUser = trim($input['notifications_smtp_user'] ?? '');
        $smtpPassword = trim($input['notifications_smtp_password'] ?? '');
        $fromEmail = trim($input['notifications_from_email'] ?? '');
        $fromName = trim($input['notifications_from_name'] ?? '');
        $testEmail = trim($input['notifications_test_email'] ?? '');

        $this->settings->set('notifications_enabled', $enabled);
        $this->settings->set('notifications_email_enabled', $emailEnabled);
        $this->settings->set('notifications_smtp_host', $smtpHost);
        $this->settings->set('notifications_smtp_port', $smtpPort);
        $this->settings->set('notifications_smtp_security', $smtpSecurity);
        $this->settings->set('notifications_smtp_user', $smtpUser);
        $this->settings->set('notifications_smtp_password', $smtpPassword);
        $this->settings->set('notifications_from_email', $fromEmail);
        $this->settings->set('notifications_from_name', $fromName);
        $this->settings->set('notifications_test_email', $testEmail);

        return [
            'enabled' => $enabled,
            'email_enabled' => $emailEnabled,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_security' => $smtpSecurity,
            'smtp_user' => $smtpUser,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'test_email' => $testEmail,
        ];
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
