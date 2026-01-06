<?php
class QuotationRepository
{
    public function __construct(private Database $db)
    {
    }

    public function forPr(int $prId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT q.*, s.name as supplier_name FROM quotations q LEFT JOIN suppliers s ON q.supplier_id = s.id WHERE q.purchase_request_id = ? ORDER BY q.created_at DESC');
        $stmt->execute([$prId]);
        return $stmt->fetchAll();
    }

    public function create(int $prId, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO quotations (purchase_request_id, supplier_id, amount, notes, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$prId, $data['supplier_id'], $data['amount'], $data['notes']]);
    }
}
