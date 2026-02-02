<?php
class NotificationLogRepository
{
    public function __construct(private Database $db)
    {
    }

    public function add(array $data): void
    {
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO notification_logs (notification_type_id, channel, recipient, status, error_message, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $data['type_id'],
            $data['channel'],
            $data['recipient'],
            $data['status'],
            $data['error_message'],
        ]);
    }

    public function latest(int $limit = 50): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT l.id, l.created_at, l.channel, l.recipient, l.status, l.error_message, t.name AS type_name '
            . 'FROM notification_logs l '
            . 'LEFT JOIN notification_types t ON t.id = l.notification_type_id '
            . 'ORDER BY l.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
