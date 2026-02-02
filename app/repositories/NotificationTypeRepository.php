<?php
class NotificationTypeRepository
{
    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->pdo()->query('SELECT id, code, name, description, channel, is_active FROM notification_types ORDER BY name');
        $types = $stmt->fetchAll();
        $roles = $this->rolesByType();
        foreach ($types as &$type) {
            $type['roles'] = $roles[$type['id']] ?? [];
            $type['is_active'] = (bool)$type['is_active'];
        }
        return $types;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT id, code, name, description, channel, is_active FROM notification_types WHERE code = ?');
        $stmt->execute([$code]);
        $type = $stmt->fetch();
        if (!$type) {
            return null;
        }
        $roles = $this->rolesByType([$type['id']]);
        $type['roles'] = $roles[$type['id']] ?? [];
        $type['is_active'] = (bool)$type['is_active'];
        return $type;
    }

    public function update(int $id, bool $active, string $channel): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE notification_types SET is_active = ?, channel = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $channel, $id]);
    }

    public function setRoles(int $id, array $roles): void
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM notification_type_roles WHERE notification_type_id = ?');
        $stmt->execute([$id]);
        $insert = $pdo->prepare('INSERT INTO notification_type_roles (notification_type_id, role) VALUES (?, ?)');
        foreach ($roles as $role) {
            $role = trim($role);
            if ($role === '') {
                continue;
            }
            $insert->execute([$id, $role]);
        }
        $pdo->commit();
    }

    public function create(string $code, string $name, string $description, string $channel): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO notification_types (code, name, description, channel, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$code, $name, $description, $channel]);
    }

    private function rolesByType(array $filterIds = []): array
    {
        $sql = 'SELECT notification_type_id, role FROM notification_type_roles';
        $params = [];
        if (!empty($filterIds)) {
            $placeholders = implode(',', array_fill(0, count($filterIds), '?'));
            $sql .= ' WHERE notification_type_id IN (' . $placeholders . ')';
            $params = $filterIds;
        }
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        $roles = [];
        foreach ($stmt->fetchAll() as $row) {
            $roles[$row['notification_type_id']][] = $row['role'];
        }
        return $roles;
    }
}
