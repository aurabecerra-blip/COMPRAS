<?php
class ReceptionRepository
{
    public function __construct(private Database $db)
    {
    }

    public function create(int $poId, array $items, int $userId, ?string $evidencePath, string $notes): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO receipts (purchase_order_id, received_by, evidence_path, notes, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$poId, $userId, $evidencePath, $notes]);
        $receiptId = (int)$this->db->pdo()->lastInsertId();
        foreach ($items as $poItemId => $quantity) {
            $this->db->pdo()->prepare('INSERT INTO receipt_items (receipt_id, po_item_id, quantity) VALUES (?, ?, ?)')
                ->execute([$receiptId, (int)$poItemId, (float)$quantity]);
        }
    }
}
