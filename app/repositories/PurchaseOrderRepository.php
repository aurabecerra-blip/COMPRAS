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
        $stmt = $this->db->pdo()->prepare('INSERT INTO purchase_orders (purchase_request_id, supplier_id, total_amount, status, created_at, updated_at) VALUES (?, ?, ?, "CREADA", NOW(), NOW())');
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
        $stmt = $this->db->pdo()->prepare('SELECT r.*, SUM(ri.quantity) as received_qty FROM receipts r LEFT JOIN receipt_items ri ON r.id = ri.receipt_id WHERE r.purchase_order_id = ? GROUP BY r.id ORDER BY r.created_at DESC');
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function close(int $poId, ?string $reason = null): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_orders SET status = "CERRADA", close_reason = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$reason, $poId]);
    }

    public function markSent(int $poId): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_orders SET status = "ENVIADA A PROVEEDOR", sent_at = NOW(), updated_at = NOW() WHERE id = ?');
        $stmt->execute([$poId]);
    }

    public function refreshStatusFromReceipts(int $poId): void
    {
        $currentStatus = $this->db->pdo()->prepare('SELECT status FROM purchase_orders WHERE id = ?');
        $currentStatus->execute([$poId]);
        $statusRow = $currentStatus->fetch();
        $baseStatus = $statusRow['status'] ?? 'CREADA';

        $itemsStmt = $this->db->pdo()->prepare('SELECT id, quantity FROM po_items WHERE purchase_order_id = ?');
        $itemsStmt->execute([$poId]);
        $items = $itemsStmt->fetchAll();

        $receivedStmt = $this->db->pdo()->prepare('SELECT po_item_id, SUM(quantity) as qty FROM receipt_items WHERE receipt_id IN (SELECT id FROM receipts WHERE purchase_order_id = ?) GROUP BY po_item_id');
        $receivedStmt->execute([$poId]);
        $receivedMap = [];
        foreach ($receivedStmt->fetchAll() as $row) {
            $receivedMap[$row['po_item_id']] = (float)$row['qty'];
        }

        $fullyReceived = true;
        $anyReceived = false;
        foreach ($items as $item) {
            $received = $receivedMap[$item['id']] ?? 0;
            if ($received > 0) {
                $anyReceived = true;
            }
            if ($received < (float)$item['quantity']) {
                $fullyReceived = false;
            }
        }

        $newStatus = $baseStatus;
        if ($fullyReceived && !empty($items)) {
            $newStatus = 'RECIBIDA TOTAL';
        } elseif ($anyReceived) {
            $newStatus = 'RECIBIDA PARCIAL';
        }

        $stmt = $this->db->pdo()->prepare('UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newStatus, $poId]);
    }
}
