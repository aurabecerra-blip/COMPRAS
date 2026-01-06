<?php
class UserRepository
{
    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->pdo()->query('SELECT id, name, email, role FROM users ORDER BY name')->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO users (name, email, role, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['role'],
            password_hash($data['password'], PASSWORD_DEFAULT),
        ]);
    }
}
