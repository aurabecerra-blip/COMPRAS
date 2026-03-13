<?php
class SupplierRepository
{
    private ?bool $hasLeaderUserColumn = null;

    public function __construct(private Database $db)
    {
    }

    public function all(?int $leaderUserId = null, bool $onlyRegistered = false): array
    {
        $supportsLeader = $this->supportsLeaderUserColumn();

        $sql = 'SELECT s.*, '
            . ($supportsLeader ? 'u.name AS leader_name,' : 'NULL AS leader_name,')
            . ' COUNT(DISTINCT q.id) AS quotations_count,
                    AVG(q.lead_time_days) AS avg_lead_time,
                    COUNT(DISTINCT po.id) AS pos_count,
                    SUM(po.total_amount) AS pos_spend,
                    SUM(po.status IN (\'CREADA\',\'ENVIADA_A_PROVEEDOR\',\'RECIBIDA_PARCIAL\')) AS open_pos,
                    MAX(CASE WHEN ev.latest_evaluation_id IS NULL THEN 0 ELSE 1 END) AS has_evaluation,
                    MAX(CASE WHEN sed.option_key IN (\'excellent\', \'complete\') THEN 1 ELSE 0 END) AS documents_complete
                FROM suppliers s '
            . ($supportsLeader ? 'LEFT JOIN users u ON u.id = s.leader_user_id ' : '')
            . 'LEFT JOIN quotations q ON q.supplier_id = s.id
                LEFT JOIN purchase_orders po ON po.supplier_id = s.id
                LEFT JOIN (
                    SELECT supplier_id, MAX(id) AS latest_evaluation_id
                    FROM supplier_evaluations
                    GROUP BY supplier_id
                ) ev ON ev.supplier_id = s.id
                LEFT JOIN supplier_evaluation_details sed
                    ON sed.evaluation_id = ev.latest_evaluation_id
                    AND sed.criterion_code = \'documents\'
                WHERE 1 = 1';

        $params = [];
        if ($supportsLeader && $leaderUserId !== null && $leaderUserId > 0) {
            $sql .= ' AND s.leader_user_id = ?';
            $params[] = $leaderUserId;
        }

        if ($onlyRegistered) {
            $sql .= " AND COALESCE(TRIM(s.nit), '') <> ''"
                . " AND COALESCE(TRIM(s.email), '') <> ''";
        }

        $sql .= ' GROUP BY s.id' . ($supportsLeader ? ', u.name' : '') . '
                ORDER BY s.name';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
        if ($this->supportsLeaderUserColumn()) {
            $stmt = $this->db->pdo()->prepare('INSERT INTO suppliers (name, nit, service, contact, email, phone, leader_user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone'], $data['leader_user_id'] ?? null]);
            return;
        }

        $stmt = $this->db->pdo()->prepare('INSERT INTO suppliers (name, nit, service, contact, email, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone']]);
    }

    public function update(int $id, array $data): void
    {
        if ($this->supportsLeaderUserColumn()) {
            $stmt = $this->db->pdo()->prepare('UPDATE suppliers SET name = ?, nit = ?, service = ?, contact = ?, email = ?, phone = ?, leader_user_id = ? WHERE id = ?');
            $stmt->execute([$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone'], $data['leader_user_id'] ?? null, $id]);
            return;
        }

        $stmt = $this->db->pdo()->prepare('UPDATE suppliers SET name = ?, nit = ?, service = ?, contact = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone'], $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->pdo()->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function supportsLeaderUserColumn(): bool
    {
        if ($this->hasLeaderUserColumn !== null) {
            return $this->hasLeaderUserColumn;
        }

        $stmt = $this->db->pdo()->prepare('SHOW COLUMNS FROM suppliers LIKE ?');
        $stmt->execute(['leader_user_id']);
        $this->hasLeaderUserColumn = (bool)$stmt->fetch();

        return $this->hasLeaderUserColumn;
    }
}
