<?php
class AdminController
{
    public function __construct(
        private SettingsRepository $settings,
        private CompanyRepository $companies,
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
        $notificationTypes = $this->notificationTypes->all();
        $notificationLogs = $this->notificationLogs->latest();
        $settingsRepo = $this->settings;
        $activeCompany = $this->companies->active();
        $companies = $this->companies->all();
        include __DIR__ . '/../views/admin/index.php';
    }

    public function users(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);

        $search = trim($_GET['search'] ?? '');
        $status = strtolower(trim($_GET['status'] ?? 'all'));
        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $users = $this->users->all($search, $status);
        $roles = $this->users->validRoles();
        include __DIR__ . '/../views/admin/users.php';
    }

    public function storeUser(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => trim($_POST['role'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['name'] === '' || $data['email'] === '' || $data['password'] === '') {
            $this->flash->add('danger', 'Nombre, email y contraseña son obligatorios.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if (!$this->users->isValidRole($data['role'])) {
            $this->flash->add('danger', 'Rol inválido.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flash->add('danger', 'El email no tiene un formato válido.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if (!CorporateEmailValidator::isValid($data['email'])) {
            $this->flash->add('danger', 'Solo se permite el dominio @aossas.com.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if ($this->users->emailExists($data['email'])) {
            $this->flash->add('danger', 'El email ya está registrado.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        $id = $this->users->create($data);
        $this->audit->log($this->auth->user()['id'], 'user_create', ['user_id' => $id, 'email' => $data['email'], 'role' => $data['role']]);
        $this->flash->add('success', 'Usuario creado.');
        header('Location: ' . route_to('admin_users'));
    }

    public function updateUser(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);

        $id = (int)($_POST['id'] ?? 0);
        $user = $this->users->findById($id);
        if (!$user) {
            $this->flash->add('danger', 'Usuario no encontrado.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => trim($_POST['role'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        $newPassword = $_POST['new_password'] ?? '';

        if ($data['name'] === '' || $data['email'] === '') {
            $this->flash->add('danger', 'Nombre y email son obligatorios.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if (!$this->users->isValidRole($data['role'])) {
            $this->flash->add('danger', 'Rol inválido.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flash->add('danger', 'El email no tiene un formato válido.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if (!CorporateEmailValidator::isValid($data['email'])) {
            $this->flash->add('danger', 'Solo se permite el dominio @aossas.com.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        if ($this->users->emailExists($data['email'], $id)) {
            $this->flash->add('danger', 'El email ya está registrado por otro usuario.');
            header('Location: ' . route_to('admin_users'));
            return;
        }

        $this->users->update($id, $data);

        if ($newPassword !== '') {
            $this->users->resetPassword($id, $newPassword);
        }

        $this->audit->log($this->auth->user()['id'], 'user_update', [
            'user_id' => $id,
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'password_reset' => $newPassword !== '',
        ]);
        $this->flash->add('success', 'Usuario actualizado.');
        header('Location: ' . route_to('admin_users'));
    }

    public function updateSettings(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);
        $companyId = (int)($_POST['company_id'] ?? 0);
        $company = trim($_POST['company_name'] ?? '');
        $nit = trim($_POST['company_nit'] ?? '');
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

        $savedCompanyId = $this->companies->upsert([
            'id' => $companyId,
            'name' => $company,
            'nit' => $nit,
            'logo_path' => $logo,
            'primary_color' => $primary,
            'secondary_color' => $accent,
        ]);

        if ($savedCompanyId > 0) {
            $this->companies->setActive($savedCompanyId);
            $active = $this->companies->active();
            $this->settings->set('company_name', (string)$active['name']);
            $this->settings->set('company_nit', (string)$active['nit']);
            $this->settings->set('brand_logo_path', (string)$active['logo_path']);
            $this->settings->set('brand_primary_color', (string)$active['primary_color']);
            $this->settings->set('brand_accent_color', (string)$active['secondary_color']);
        }

        $this->settings->set('form_areas', $areas);
        $this->settings->set('form_cost_centers', $costCenters);
        $this->audit->log($this->auth->user()['id'], 'settings_update', [
            'company_id' => $savedCompanyId,
            'company' => $company,
            'nit' => $nit,
            'logo' => $logo,
            'primary' => $primary,
            'accent' => $accent,
            'form_areas' => $areas,
            'form_cost_centers' => $costCenters,
        ]);
        $this->flash->add('success', 'Configuración guardada');
        header('Location: ' . route_to('admin'));
    }


    public function switchActiveCompany(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['administrador']);

        $companyId = (int)($_POST['active_company_id'] ?? 0);
        if (!$this->companies->setActive($companyId)) {
            $this->flash->add('danger', 'No se pudo cambiar la empresa activa.');
            header('Location: ' . route_to('admin'));
            return;
        }

        $active = $this->companies->active();
        $this->settings->set('company_name', (string)$active['name']);
        $this->settings->set('company_nit', (string)$active['nit']);
        $this->settings->set('brand_logo_path', (string)$active['logo_path']);
        $this->settings->set('brand_primary_color', (string)$active['primary_color']);
        $this->settings->set('brand_accent_color', (string)$active['secondary_color']);
        $this->audit->log($this->auth->user()['id'], 'active_company_switch', ['company_id' => $companyId]);

        $this->flash->add('success', 'Empresa activa actualizada.');
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
        $error = $this->notifications->sendTestEmail($recipient);
        if ($error !== null) {
            $this->flash->add('danger', 'Error al enviar correo de prueba: ' . $error);
        } else {
            $this->flash->add('success', 'Correo de prueba enviado (revisa el log).');
        }
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
}
