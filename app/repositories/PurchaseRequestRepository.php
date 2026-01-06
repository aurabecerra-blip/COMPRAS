<?php
class PurchaseRequestRepository
{
    private const STATUSES = ['BORRADOR', 'ENVIADA', 'APROBADA', 'RECHAZADA', 'CANCELADA'];

    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->pdo()->query('SELECT pr.*, u.name as requester_name FROM purchase_requests pr LEFT JOIN users u ON pr.requester_id = u.id ORDER BY pr.created_at DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM purchase_requests WHERE id = ?');
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
        $stmt = $this->db->pdo()->prepare('INSERT INTO purchase_requests (requester_id, title, justification, area, cost_center, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, "BORRADOR", NOW(), NOW())');
        $stmt->execute([$requesterId, $data['title'], $data['justification'], $data['area'], $data['cost_center'], $data['description']]);
        $prId = (int)$this->db->pdo()->lastInsertId();
        foreach ($data['items'] as $item) {
            $this->addItem($prId, $item);
        }
        return $prId;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE purchase_requests SET title = ?, justification = ?, area = ?, cost_center = ?, description = ?, updated_at = NOW() WHERE id = ? AND status = "BORRADOR"');
        $stmt->execute([$data['title'], $data['justification'], $data['area'], $data['cost_center'], $data['description'], $id]);
        $this->db->pdo()->prepare('DELETE FROM pr_items WHERE purchase_request_id = ?')->execute([$id]);
        foreach ($data['items'] as $item) {
            $this->addItem($id, $item);
        }
    }

    private function addItem(int $prId, array $item): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO pr_items (purchase_request_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)');
        $stmt->execute([$prId, $item['description'], (float)$item['quantity'], (float)$item['unit_price']]);
    }

    public function items(int $prId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM pr_items WHERE purchase_request_id = ?');
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

    public function canSend(array $pr): bool
    {
        return $pr['title'] !== '' && $pr['justification'] !== '' && $pr['area'] !== '' && $pr['cost_center'] !== '' && !empty($this->items((int)$pr['id']));
    }
}
