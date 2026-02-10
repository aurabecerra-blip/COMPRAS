<?php
class AuthController
{
    public function __construct(
        private Auth $auth,
        private Flash $flash,
        private AuditLogger $audit,
        private UserRepository $users
    )
    {
    }

    public function login(): void
    {
        if (!$this->users->hasActiveAdmin()) {
            header('Location: ' . route_to('first_use'));
            return;
        }
        include __DIR__ . '/../views/auth/login.php';
    }

    public function handleLogin(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($this->auth->attempt($email, $password)) {
            $user = $this->auth->user();
            $this->audit->log($user['id'], 'login', ['email' => $email]);
            header('Location: ' . route_to('dashboard'));
            exit;
        }
        header('Location: ' . route_to('login'));
    }

    public function firstUse(): void
    {
        if ($this->users->hasActiveAdmin()) {
            header('Location: ' . route_to('login'));
            return;
        }
        include __DIR__ . '/../views/auth/first_use.php';
    }

    public function handleFirstUse(): void
    {
        if ($this->users->hasActiveAdmin()) {
            header('Location: ' . route_to('login'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $this->flash->add('danger', 'Todos los campos son obligatorios.');
            header('Location: ' . route_to('first_use'));
            return;
        }

        if (!CorporateEmailValidator::isValid($email)) {
            $this->flash->add('danger', 'El email debe terminar en @aossas.com.');
            header('Location: ' . route_to('first_use'));
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->flash->add('danger', 'Las contraseñas no coinciden.');
            header('Location: ' . route_to('first_use'));
            return;
        }

        if ($this->users->emailExists($email)) {
            $this->flash->add('danger', 'El email ya existe.');
            header('Location: ' . route_to('first_use'));
            return;
        }

        $id = $this->users->create([
            'name' => $name,
            'email' => $email,
            'role' => 'administrador',
            'password' => $password,
            'is_active' => 1,
        ]);

        $this->audit->log(null, 'first_admin_created', ['user_id' => $id, 'email' => $email]);
        $this->flash->add('success', 'Administrador inicial creado. Inicia sesión.');
        header('Location: ' . route_to('login'));
    }

    public function logout(): void
    {
        $user = $this->auth->user();
        $this->audit->log($user['id'] ?? null, 'logout', []);
        $this->auth->logout();
        header('Location: ' . route_to('login'));
    }
}
