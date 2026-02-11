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

        $stmt->execute([$purchaseRequestId]);
        return $stmt->fetch() ?: ['id' => (int)$this->db->pdo()->lastInsertId(), 'purchase_request_id' => $purchaseRequestId, 'status' => 'BORRADOR'];
    }

    public function quotationsByProcess(int $processId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT q.*, s.name AS supplier_name
            FROM supplier_quotations q
            INNER JOIN suppliers s ON s.id = q.supplier_id
            WHERE q.selection_process_id = ?
            ORDER BY q.valor_total ASC, q.created_at ASC');
        $stmt->execute([$processId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['files'] = $this->filesByQuotation((int)$row['id']);
        }
        unset($row);
        return $rows;
    }

    public function addQuotation(int $processId, array $data): int
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO supplier_quotations
            (selection_process_id, purchase_request_id, supplier_id, quote_number, quotation_date, valor_subtotal, valor_total, total_value, currency, delivery_term_days, payment_terms, evaluacion_pago, warranty, evaluacion_postventa, technical_compliance, observations, ofrece_descuento, tipo_descuento, descuento_valor, experiencia_anios, certificaciones_tecnicas, certificaciones_comerciales, lista_certificaciones, archivo_cotizacion_url, archivo_soporte_experiencia_url, archivo_certificaciones_url, evidence_file_path, evidence_original_name, mime_type, file_size, uploaded_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');

        $stmt->execute([
            $processId,
            $data['purchase_request_id'],
            $data['supplier_id'],
            $data['quote_number'] ?? null,
            $data['quotation_date'],
            $data['valor_subtotal'],
            $data['valor_total'],
            $data['valor_total'],
            $data['currency'] ?? 'COP',
            $data['delivery_term_days'],
            $data['payment_terms'],
            $data['evaluacion_pago'],
            $data['warranty'],
            $data['evaluacion_postventa'],
            'CUMPLE',
            $data['observations'] ?? null,
            $data['ofrece_descuento'],
            $data['tipo_descuento'],
            $data['descuento_valor'],
            $data['experiencia_anios'],
            $data['certificaciones_tecnicas'],
            $data['certificaciones_comerciales'],
            $data['lista_certificaciones'],
            $data['archivo_cotizacion_url'],
            $data['archivo_soporte_experiencia_url'],
            $data['archivo_certificaciones_url'],
            $data['archivo_cotizacion_url'],
            $data['evidence_original_name'],
            $data['mime_type'] ?? null,
            $data['file_size'] ?? null,
            $data['uploaded_by'],
        ]);

        return (int)$this->db->pdo()->lastInsertId();
    }

    public function addQuotationFile(int $quotationId, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('INSERT INTO supplier_quotation_files
            (quotation_id, file_path, original_name, mime_type, file_size, uploaded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $quotationId,
            $data['file_path'],
            $data['original_name'],
            $data['mime_type'] ?? null,
            $data['file_size'] ?? null,
            $data['uploaded_by'],
        ]);
    }

    public function filesByQuotation(int $quotationId): array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM supplier_quotation_files WHERE quotation_id = ? ORDER BY created_at ASC');
        $stmt->execute([$quotationId]);
        return $stmt->fetchAll();
    }

    public function replaceScores(int $processId, array $scoreSummaries): void
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM supplier_selection_scores WHERE selection_process_id = ?');
            $delete->execute([$processId]);

            $insert = $pdo->prepare('INSERT INTO supplier_selection_scores
                (selection_process_id, quotation_id, supplier_id, criterion_code, criterion_name, criterion_weight, score_value, input_data_json, formula_applied, price_score, delivery_score, payment_score, warranty_score, technical_score, total_score, rank_position, is_winner, details_json, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 0, ?, ?, ?, ?, NOW(), NOW())');

            foreach ($scoreSummaries as $summary) {
                foreach ($summary['criteria_rows'] as $detail) {
                    $isTotal = $detail['criterion_code'] === 'TOTAL';
                    $insert->execute([
                        $processId,
                        $summary['quotation_id'],
                        $summary['supplier_id'],
                        $detail['criterion_code'],
                        $detail['criterion_name'],
                        $detail['criterion_weight'],
                        $detail['score_value'],
                        json_encode($detail['input_data'], JSON_UNESCAPED_UNICODE),
                        $detail['formula_applied'],
                        $isTotal ? $summary['total_score'] : 0,
                        $isTotal ? $summary['rank_position'] : null,
                        $isTotal ? ($summary['is_winner'] ? 1 : 0) : 0,
                        json_encode($summary, JSON_UNESCAPED_UNICODE),
                    ]);
                }
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
            WHERE sc.selection_process_id = ? AND sc.criterion_code = "TOTAL"
            ORDER BY sc.rank_position ASC, sc.score_value DESC');
        $stmt->execute([$processId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $detailStmt = $this->db->pdo()->prepare('SELECT criterion_code, criterion_name, criterion_weight, score_value
                FROM supplier_selection_scores
                WHERE selection_process_id = ? AND quotation_id = ?
                ORDER BY id ASC');
            $detailStmt->execute([$processId, (int)$row['quotation_id']]);
            $row['details'] = $detailStmt->fetchAll();
            $row['total_score'] = $row['score_value'];
        }
        unset($row);

        return $rows;
    }

    public function countDistinctSuppliers(int $processId): int
    {
        $stmt = $this->db->pdo()->prepare('SELECT COUNT(DISTINCT supplier_id) FROM supplier_quotations WHERE selection_process_id = ?');
        $stmt->execute([$processId]);
        return (int)$stmt->fetchColumn();
    }

    public function updateProcessStatus(int $processId, array $data): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE supplier_selection_processes
            SET status = ?, winner_supplier_id = ?, justification_text = ?, observations = ?, acta_pdf_url = ?, selected_at = ?, updated_at = NOW()
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
