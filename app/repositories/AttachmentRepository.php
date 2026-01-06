<?php
class AttachmentRepository
{
    public function __construct(private Database $db)
    {
    }

    public function add(int $prId, string $path, string $originalName, int $userId): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO attachments (purchase_request_id, file_path, original_name, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$prId, $path, $originalName, $userId]);
    }

    public function forPr(int $prId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM attachments WHERE purchase_request_id = ?');
        $stmt->execute([$prId]);
        return $stmt->fetchAll();
    }
}
