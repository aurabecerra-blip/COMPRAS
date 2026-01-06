<?php
class PurchaseOrderRepository
{
    public function __construct(private Database $db)
    {
    }

    public function forPr(int $prId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM purchase_orders WHERE purchase_request_id = ?');
        $stmt->execute([$prId]);
        return $stmt->fetchAll();
    }

    public function create(int $prId, array $data): int
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO purchase_orders (purchase_request_id, supplier_id, total_amount, status, created_at) VALUES (?, ?, ?, "ABIERTA", NOW())');
        $stmt->execute([$prId, $data['supplier_id'], $data['total_amount']]);
        $poId = (int)$this->db->pdo()->lastInsertId();
        foreach ($data['items'] as $item) {
            $this->addItem($poId, $item);
        }
        return $poId;
    }

    private function addItem(int $poId, array $item): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO po_items (purchase_order_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)');
        $stmt->execute([$poId, $item['description'], (float)$item['quantity'], (float)$item['unit_price']]);
    }

    public function receipts(int $poId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT r.*, SUM(ri.quantity) as received_qty FROM receipts r LEFT JOIN receipt_items ri ON r.id = ri.receipt_id WHERE r.purchase_order_id = ? GROUP BY r.id');
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function close(int $poId, ?string $reason = null): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_orders SET status = "CERRADA", close_reason = ? WHERE id = ?');
        $stmt->execute([$reason, $poId]);
    }
}
