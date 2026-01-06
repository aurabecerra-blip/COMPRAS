<?php
class AuditLogger
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function log(?int $userId, string $action, array $detail): void
    {
        $userId = $this->existingUserIdOrNull($userId);
        $stmt = $this->db->pdo()->prepare('INSERT INTO audit_log (user_id, action, detail_json, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$userId, $action, json_encode($detail, JSON_UNESCAPED_UNICODE)]);
    }

    private function existingUserIdOrNull(?int $userId): ?int
    {
        if ($userId === null) {
            return null;
        }

        $stmt = $this->db->pdo()->prepare('SELECT 1 FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        return $stmt->fetchColumn() !== false ? $userId : null;
    }
}
