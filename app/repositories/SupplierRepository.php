<?php
class SupplierRepository
{
    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        $sql = 'SELECT s.*, 
                    COUNT(DISTINCT q.id) AS quotations_count,
                    AVG(q.lead_time_days) AS avg_lead_time,
                    COUNT(DISTINCT po.id) AS pos_count,
                    SUM(po.total_amount) AS pos_spend,
                    SUM(po.status IN (\'CREADA\',\'ENVIADA_A_PROVEEDOR\',\'RECIBIDA_PARCIAL\')) AS open_pos
                FROM suppliers s
                LEFT JOIN quotations q ON q.supplier_id = s.id
                LEFT JOIN purchase_orders po ON po.supplier_id = s.id
                GROUP BY s.id
                ORDER BY s.name';
        return $this->db->pdo()->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM suppliers WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    public function findOrCreateByName(string $name): int
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new InvalidArgumentException('El nombre del proveedor es obligatorio.');
        }

        $existing = $this->findByName($cleanName);
        if ($existing) {
            return (int)$existing['id'];
        }

        $stmt = $this->db->pdo()->prepare('INSERT INTO suppliers (name, nit, service, contact, email, phone, created_at) VALUES (?, NULL, NULL, NULL, NULL, NULL, NOW())');
        $stmt->execute([$cleanName]);

        return (int)$this->db->pdo()->lastInsertId();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO suppliers (name, nit, service, contact, email, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone']]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE suppliers SET name = ?, nit = ?, service = ?, contact = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone'], $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->pdo()->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
    }
}
