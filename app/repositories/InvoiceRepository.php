<?php
class InvoiceRepository
{
    public function __construct(private Database $db)
    {
    }

    public function forPo(int $poId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM invoices WHERE purchase_order_id = ?');
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function create(int $poId, array $data, int $userId): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO invoices (purchase_order_id, invoice_number, amount, registered_by, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$poId, $data['invoice_number'], $data['amount'], $userId]);
    }
}
