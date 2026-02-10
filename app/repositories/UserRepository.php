<?php
class UserRepository
{
    private const VALID_ROLES = ['administrador', 'aprobador', 'compras', 'recepcion', 'solicitante'];

    public function __construct(private Database $db)
    {
    }

    public function all(string $search = ''): array
    {
        $sql = 'SELECT id, name, email, role, is_active, created_at FROM users';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ? OR role LIKE ?';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY name';
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT id, name, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE LOWER(email) = LOWER(?)';
        $params = [$email];
        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO users (name, email, role, password_hash, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $data['name'],
            strtolower($data['email']),
            $data['role'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int)$this->db->pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE users SET name = ?, email = ?, role = ?, is_active = ? WHERE id = ?');
        $stmt->execute([
            $data['name'],
            strtolower($data['email']),
            $data['role'],
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public function resetPassword(int $id, string $password): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);
    }

    public function hasActiveAdmin(): bool
    {
        $stmt = $this->db->pdo()->query("SELECT id FROM users WHERE role = 'administrador' AND is_active = 1 LIMIT 1");
        return (bool)$stmt->fetchColumn();
    }

    public function emailsByRoles(array $roles): array
    {
        $roles = array_values(array_unique(array_filter(array_map('trim', $roles))));
        if (empty($roles)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->db->pdo()->prepare('SELECT email FROM users WHERE role IN (' . $placeholders . ') AND is_active = 1');
        $stmt->execute($roles);
        $emails = array_column($stmt->fetchAll(), 'email');
        return array_values(array_unique(array_filter(array_map('trim', $emails))));
    }

    public function isValidRole(string $role): bool
    {
        return in_array($role, self::VALID_ROLES, true);
    }

    public function validRoles(): array
    {
        return self::VALID_ROLES;
    }
}
