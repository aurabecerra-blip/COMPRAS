<?php
class SupplierRepository
{
    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->pdo()->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO suppliers (name, contact, email, phone, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$data['name'], $data['contact'], $data['email'], $data['phone']]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE suppliers SET name = ?, contact = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['contact'], $data['email'], $data['phone'], $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->pdo()->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
    }
}
