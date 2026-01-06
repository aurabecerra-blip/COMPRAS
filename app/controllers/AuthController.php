<?php
class AuthController
{
    public function __construct(private Auth $auth, private Flash $flash, private AuditLogger $audit)
    {
    }

    public function login(): void
    {
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

    public function logout(): void
    {
        $user = $this->auth->user();
        $this->audit->log($user['id'] ?? null, 'logout', []);
        $this->auth->logout();
        header('Location: ' . route_to('login'));
    }
}
