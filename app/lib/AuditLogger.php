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
        $stmt = $this->db->pdo()->prepare('INSERT INTO audit_log (user_id, action, detail_json, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$userId, $action, json_encode($detail, JSON_UNESCAPED_UNICODE)]);
    }
}
