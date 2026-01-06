<?php
class SettingsRepository
{
    public function __construct(private Database $db)
    {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->pdo()->prepare('SELECT value FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row['value'] ?? $default;
    }

    public function all(): array
    {
        return $this->db->pdo()->query('SELECT `key`, value FROM settings')->fetchAll();
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->execute([$key, $value]);
    }
}
