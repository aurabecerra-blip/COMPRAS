<?php
class SupplierSelectionRepository
{
    public function __construct(private Database $db)
    {
    }

    public function getOrCreateProcess(int $purchaseRequestId, int $evaluatorId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM supplier_selection_processes WHERE purchase_request_id = ? LIMIT 1');
        $stmt->execute([$purchaseRequestId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        $insert = $this->db->pdo()->prepare('INSERT INTO supplier_selection_processes
            (purchase_request_id, evaluator_user_id, status, created_at, updated_at)
            VALUES (?, ?, "BORRADOR", NOW(), NOW())');
        $insert->execute([$purchaseRequestId, $evaluatorId]);

        $id = (int)$this->db->pdo()->lastInsertId();
        $stmt->execute([$purchaseRequestId]);
        return $stmt->fetch() ?: ['id' => $id, 'purchase_request_id' => $purchaseRequestId, 'status' => 'BORRADOR'];
    }

    public function quotationsByProcess(int $processId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT q.*, s.name AS supplier_name
            FROM supplier_quotations q
            INNER JOIN suppliers s ON s.id = q.supplier_id
            WHERE q.selection_process_id = ?
            ORDER BY q.total_value ASC');
        $stmt->execute([$processId]);
        return $stmt->fetchAll();
    }

    public function addQuotation(int $processId, array $data): int
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO supplier_quotations
            (selection_process_id, supplier_id, quotation_date, total_value, currency, delivery_term_days, payment_terms, warranty, technical_compliance, observations, evidence_file_path, evidence_original_name, mime_type, file_size, uploaded_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $processId,
            $data['supplier_id'],
            $data['quotation_date'],
            $data['total_value'],
            $data['currency'] ?? 'COP',
            $data['delivery_term_days'],
            $data['payment_terms'],
            $data['warranty'],
            $data['technical_compliance'],
            $data['observations'] ?? null,
            $data['evidence_file_path'],
            $data['evidence_original_name'],
            $data['mime_type'] ?? null,
            $data['file_size'] ?? null,
            $data['uploaded_by'],
        ]);
        return (int)$this->db->pdo()->lastInsertId();
    }

    public function replaceScores(int $processId, array $scores): void
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM supplier_selection_scores WHERE selection_process_id = ?');
            $delete->execute([$processId]);

            $insert = $pdo->prepare('INSERT INTO supplier_selection_scores
                (selection_process_id, quotation_id, supplier_id, price_score, delivery_score, payment_score, warranty_score, technical_score, total_score, rank_position, is_winner, details_json, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            foreach ($scores as $row) {
                $insert->execute([
                    $processId,
                    $row['quotation_id'],
                    $row['supplier_id'],
                    $row['price_score'],
                    $row['delivery_score'],
                    $row['payment_score'],
                    $row['warranty_score'],
                    $row['technical_score'],
                    $row['total_score'],
                    $row['rank_position'],
                    $row['is_winner'] ? 1 : 0,
                    json_encode($row['details'], JSON_UNESCAPED_UNICODE),
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function scoresByProcess(int $processId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT sc.*, s.name AS supplier_name
            FROM supplier_selection_scores sc
            INNER JOIN suppliers s ON s.id = sc.supplier_id
            WHERE sc.selection_process_id = ?
            ORDER BY sc.rank_position ASC, sc.total_score DESC');
        $stmt->execute([$processId]);
        return $stmt->fetchAll();
    }

    public function updateProcessStatus(int $processId, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE supplier_selection_processes
            SET status = ?, winner_supplier_id = ?, winner_justification = ?, observations = ?, selection_pdf_path = ?, selected_at = ?, updated_at = NOW()
            WHERE id = ?');
        $stmt->execute([
            $data['status'],
            $data['winner_supplier_id'] ?? null,
            $data['winner_justification'] ?? null,
            $data['observations'] ?? null,
            $data['selection_pdf_path'] ?? null,
            $data['selected_at'] ?? null,
            $processId,
        ]);
    }
}
