<?php
class Auth
{
    private Database $db;
    private Flash $flash;

    public function __construct(Database $db, Flash $flash)
    {
        $this->db = $db;
        $this->flash = $flash;
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $normalizedRole = strtolower($user['role']);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $normalizedRole,
                'name' => $user['name'],
            ];
            $this->flash->add('success', 'Inicio de sesión exitoso');
            return true;
        }
        $this->flash->add('danger', 'Credenciales inválidas');
        return false;
    }

    public function user(): ?array
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $user = $_SESSION['user'];
        $user['role'] = strtolower($user['role'] ?? '');
        $_SESSION['user']['role'] = $user['role'];
        return $user;
    }

    public function requireRole(array $roles): void
    {
        $user = $this->user();
        $role = strtolower($user['role'] ?? '');
        if (!$user || !in_array($role, $roles, true)) {
            header('Location: ' . route_to('login'));
            exit;
        }
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
    }
}
