<?php
class AttachmentRepository
{
    public function __construct(private Database $db)
    {
    }

    public function add(string $entityType, int $entityId, string $path, string $originalName, int $userId): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO attachments (entity_type, entity_id, file_path, original_name, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$entityType, $entityId, $path, $originalName, $userId]);
    }

    public function forEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM attachments WHERE entity_type = ? AND entity_id = ?');
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll();
    }
}
