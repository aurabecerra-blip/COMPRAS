<?php
class SupplierRepository
{
    private ?bool $hasLeaderUserColumn = null;
    private ?bool $hasDocumentsCompleteColumn = null;
    private ?bool $hasDeletedAtColumn = null;

    public function __construct(private Database $db)
    {
    }

    public function all(?int $leaderUserId = null): array
    {
        $supportsLeader = $this->supportsLeaderUserColumn();
        $supportsDocumentsComplete = $this->supportsDocumentsCompleteColumn();
        $documentsSource = $supportsDocumentsComplete
            ? 'COALESCE(s.documents_complete, 0)'
            : 'MAX(CASE WHEN sed.option_key IN (\'excellent\', \'complete\') THEN 1 ELSE 0 END)';

        $sql = 'SELECT s.*, '
            . ($supportsLeader ? 'u.name AS leader_name,' : 'NULL AS leader_name,')
            . ' COUNT(DISTINCT q.id) AS quotations_count,
                    AVG(q.lead_time_days) AS avg_lead_time,
                    COUNT(DISTINCT po.id) AS pos_count,
                    SUM(po.total_amount) AS pos_spend,
                    SUM(po.status IN (\'CREADA\',\'ENVIADA_A_PROVEEDOR\',\'RECIBIDA_PARCIAL\')) AS open_pos,
                    MAX(CASE WHEN ev.latest_evaluation_id IS NULL THEN 0 ELSE 1 END) AS has_evaluation,
                    ' . $documentsSource . ' AS documents_complete
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

        if ($this->supportsDeletedAtColumn()) {
            $sql .= ' AND s.deleted_at IS NULL';
        }

        $params = [];
        if ($supportsLeader && $leaderUserId !== null && $leaderUserId > 0) {
            $sql .= ' AND s.leader_user_id = ?';
            $params[] = $leaderUserId;
        }

        $sql .= ' GROUP BY s.id' . ($supportsLeader ? ', u.name' : '') . ($supportsDocumentsComplete ? ', s.documents_complete' : '') . '
                ORDER BY s.name';

        try {
            $stmt = $this->db->pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable) {
            return $this->allBasic($supportsLeader, $leaderUserId);
        }
    }

    private function allBasic(bool $supportsLeader, ?int $leaderUserId = null): array
    {
        $supportsDocumentsComplete = $this->supportsDocumentsCompleteColumn();

        $sql = 'SELECT s.*, '
            . ($supportsLeader ? 'u.name AS leader_name' : 'NULL AS leader_name')
            . ', 0 AS quotations_count,
                NULL AS avg_lead_time,
                0 AS pos_count,
                NULL AS pos_spend,
                0 AS open_pos,
                0 AS has_evaluation,
                ' . ($supportsDocumentsComplete ? 'COALESCE(s.documents_complete, 0)' : '0') . ' AS documents_complete
            FROM suppliers s '
            . ($supportsLeader ? 'LEFT JOIN users u ON u.id = s.leader_user_id ' : '')
            . 'WHERE 1 = 1';

        if ($supportsDocumentsComplete && $this->supportsDeletedAtColumn()) {
            $sql .= ' AND s.deleted_at IS NULL';
        } elseif ($this->supportsDeletedAtColumn()) {
            $sql .= ' AND s.deleted_at IS NULL';
        }

        $params = [];
        if ($supportsLeader && $leaderUserId !== null && $leaderUserId > 0) {
            $sql .= ' AND s.leader_user_id = ?';
            $params[] = $leaderUserId;
        }

        $sql .= ' ORDER BY s.name';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT * FROM suppliers WHERE id = ?';
        if ($this->supportsDeletedAtColumn()) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByName(string $name): ?array
    {
        $sql = 'SELECT * FROM suppliers WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))';
        if ($this->supportsDeletedAtColumn()) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->pdo()->prepare($sql);
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
        $supportsLeader = $this->supportsLeaderUserColumn();
        $supportsDocumentsComplete = $this->supportsDocumentsCompleteColumn();

        $columns = ['name', 'nit', 'service', 'contact', 'email', 'phone'];
        $values = [$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone']];

        if ($supportsLeader) {
            $columns[] = 'leader_user_id';
            $values[] = $data['leader_user_id'] ?? null;
        }

        if ($supportsDocumentsComplete) {
            $columns[] = 'documents_complete';
            $values[] = (int)($data['documents_complete'] ?? 0) === 1 ? 1 : 0;
        }

        $columns[] = 'created_at';
        $placeholders = implode(', ', array_fill(0, count($columns) - 1, '?')) . ', NOW()';
        $stmt = $this->db->pdo()->prepare('INSERT INTO suppliers (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')');
        $stmt->execute($values);
    }

    public function update(int $id, array $data): void
    {
        $supportsLeader = $this->supportsLeaderUserColumn();
        $supportsDocumentsComplete = $this->supportsDocumentsCompleteColumn();

        $setParts = ['name = ?', 'nit = ?', 'service = ?', 'contact = ?', 'email = ?', 'phone = ?'];
        $values = [$data['name'], $data['nit'], $data['service'], $data['contact'], $data['email'], $data['phone']];

        if ($supportsLeader && array_key_exists('leader_user_id', $data)) {
            $setParts[] = 'leader_user_id = ?';
            $values[] = $data['leader_user_id'] ?? null;
        }

        if ($supportsDocumentsComplete && array_key_exists('documents_complete', $data)) {
            $setParts[] = 'documents_complete = ?';
            $values[] = (int)($data['documents_complete'] ?? 0) === 1 ? 1 : 0;
        }

        $values[] = $id;
        $stmt = $this->db->pdo()->prepare('UPDATE suppliers SET ' . implode(', ', $setParts) . ' WHERE id = ?');
        $stmt->execute($values);
    }

    public function canAssignLeader(): bool
    {
        return $this->supportsLeaderUserColumn();
    }

    public function canManageDocumentsComplete(): bool
    {
        return $this->supportsDocumentsCompleteColumn();
    }

    public function delete(int $id): void
    {
        $pdo = $this->db->pdo();

        $blockingChecks = [
            'selected_process' => 'SELECT EXISTS(SELECT 1 FROM supplier_selection_processes WHERE winner_supplier_id = ?) AS blocked',
            'selected_provider_evaluation' => 'SELECT EXISTS(SELECT 1 FROM provider_selection_evaluations WHERE winner_provider_id = ?) AS blocked',
            'purchase_orders' => 'SELECT EXISTS(SELECT 1 FROM purchase_orders WHERE supplier_id = ?) AS blocked',
            'approved_requests' => 'SELECT EXISTS(SELECT 1 FROM purchase_requests WHERE selected_supplier_id = ? AND status = "APROBADA") AS blocked',
            'active_processes' => 'SELECT EXISTS(SELECT 1 FROM supplier_selection_processes p INNER JOIN supplier_quotations q ON q.selection_process_id = p.id WHERE q.supplier_id = ? AND p.status <> "CERRADO") AS blocked',
        ];

        foreach ($blockingChecks as $reason => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() === 1) {
                throw new RuntimeException($reason);
            }
        }

        if (! $this->supportsDeletedAtColumn()) {
            throw new RuntimeException('soft_delete_required');
        }

        $stmt = $pdo->prepare('UPDATE suppliers SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function supportsDeletedAtColumn(): bool
    {
        if ($this->hasDeletedAtColumn !== null) {
            return $this->hasDeletedAtColumn;
        }

        $stmt = $this->db->pdo()->prepare('SHOW COLUMNS FROM suppliers LIKE ?');
        $stmt->execute(['deleted_at']);
        $this->hasDeletedAtColumn = (bool)$stmt->fetch();

        return $this->hasDeletedAtColumn;
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

    private function supportsDocumentsCompleteColumn(): bool
    {
        if ($this->hasDocumentsCompleteColumn !== null) {
            return $this->hasDocumentsCompleteColumn;
        }

        $stmt = $this->db->pdo()->prepare('SHOW COLUMNS FROM suppliers LIKE ?');
        $stmt->execute(['documents_complete']);
        $this->hasDocumentsCompleteColumn = (bool)$stmt->fetch();

        return $this->hasDocumentsCompleteColumn;
    }
}
