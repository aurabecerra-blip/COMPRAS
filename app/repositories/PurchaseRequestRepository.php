<?php
class PurchaseRequestRepository
{
    private const STATUSES = ['BORRADOR', 'ENVIADA', 'APROBADA', 'RECHAZADA', 'CANCELADA'];
    private ?bool $supportsDescription = null;

    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->pdo()->query('SELECT pr.*, u.name as requester_name FROM purchase_requests pr LEFT JOIN users u ON pr.requester_id = u.id ORDER BY pr.created_at DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT pr.*, u.email AS requester_email FROM purchase_requests pr LEFT JOIN users u ON pr.requester_id = u.id WHERE pr.id = ?');
        $stmt->execute([$id]);
        $pr = $stmt->fetch();
        if (!$pr) {
            return null;
        }
        $pr['items'] = $this->items($id);
        return $pr;
    }

    public function create(int $requesterId, array $data): int
    {
        $tracking = $this->generateTrackingCode();
        if ($this->supportsDescriptionColumn()) {
            $stmt = $this->db->pdo()->prepare('INSERT INTO purchase_requests (requester_id, tracking_code, title, justification, area, cost_center, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, "BORRADOR", NOW(), NOW())');
            $stmt->execute([$requesterId, $tracking, $data['title'], $data['justification'], $data['area'], $data['cost_center'], $data['description']]);
        } else {
            $stmt = $this->db->pdo()->prepare('INSERT INTO purchase_requests (requester_id, tracking_code, title, justification, area, cost_center, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, "BORRADOR", NOW(), NOW())');
            $stmt->execute([$requesterId, $tracking, $data['title'], $data['justification'], $data['area'], $data['cost_center']]);
        }
        $prId = (int)$this->db->pdo()->lastInsertId();
        foreach ($data['items'] as $item) {
            $this->addItem($prId, $item);
        }
        return $prId;
    }

    public function update(int $id, array $data): void
    {
        if ($this->supportsDescriptionColumn()) {
            $stmt = $this->db->pdo()->prepare('UPDATE purchase_requests SET title = ?, justification = ?, area = ?, cost_center = ?, description = ?, updated_at = NOW() WHERE id = ? AND status = "BORRADOR"');
            $stmt->execute([$data['title'], $data['justification'], $data['area'], $data['cost_center'], $data['description'], $id]);
        } else {
            $stmt = $this->db->pdo()->prepare('UPDATE purchase_requests SET title = ?, justification = ?, area = ?, cost_center = ?, updated_at = NOW() WHERE id = ? AND status = "BORRADOR"');
            $stmt->execute([$data['title'], $data['justification'], $data['area'], $data['cost_center'], $id]);
        }
        $this->db->pdo()->prepare('DELETE FROM purchase_request_items WHERE purchase_request_id = ?')->execute([$id]);
        foreach ($data['items'] as $item) {
            $this->addItem($id, $item);
        }
    }

    private function addItem(int $prId, array $item): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO purchase_request_items (purchase_request_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)');
        $stmt->execute([$prId, $item['description'], (float)$item['quantity'], (float)$item['unit_price']]);
    }

    public function items(int $prId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM purchase_request_items WHERE purchase_request_id = ?');
        $stmt->execute([$prId]);
        return $stmt->fetchAll();
    }

    public function changeStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Estado inválido');
        }
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_requests SET status = ?, rejection_reason = NULL, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function reject(int $id, string $reason): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_requests SET status = "RECHAZADA", rejection_reason = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$reason, $id]);
    }

    public function selectSupplier(int $id, int $supplierId, string $notes): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_requests SET selected_supplier_id = ?, selection_notes = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$supplierId, $notes, $id]);
    }

    public function canSend(array $pr): bool
    {
        return $pr['title'] !== '' && $pr['justification'] !== '' && $pr['area'] !== '' && $pr['cost_center'] !== '' && !empty($this->items((int)$pr['id']));
    }

    public function findByTracking(string $tracking): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT pr.*, u.email AS requester_email FROM purchase_requests pr LEFT JOIN users u ON pr.requester_id = u.id WHERE tracking_code = ?');
        $stmt->execute([$tracking]);
        $pr = $stmt->fetch();
        if (!$pr) {
            return null;
        }
        $pr['items'] = $this->items((int)$pr['id']);
        return $pr;
    }

    private function generateTrackingCode(): string
    {
        do {
            $code = 'PR-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $this->db->pdo()->prepare('SELECT id FROM purchase_requests WHERE tracking_code = ?');
            $stmt->execute([$code]);
            $exists = $stmt->fetch();
        } while ($exists);
        return $code;
    }

    private function supportsDescriptionColumn(): bool
    {
        if ($this->supportsDescription !== null) {
            return $this->supportsDescription;
        }

        $stmt = $this->db->pdo()->query("SHOW COLUMNS FROM purchase_requests LIKE 'description'");
        $this->supportsDescription = (bool)$stmt->fetch();
        return $this->supportsDescription;
    }
}
