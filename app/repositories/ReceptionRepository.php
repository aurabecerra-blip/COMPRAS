<?php
class ReceptionRepository
{
    public function __construct(private Database $db)
    {
    }

    public function create(int $poId, array $items, int $userId): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO receipts (purchase_order_id, received_by, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$poId, $userId]);
        $receiptId = (int)$this->db->pdo()->lastInsertId();
        foreach ($items as $item) {
            $this->db->pdo()->prepare('INSERT INTO receipt_items (receipt_id, description, quantity) VALUES (?, ?, ?)')
                ->execute([$receiptId, $item['description'], (float)$item['quantity']]);
        }
    }
}
