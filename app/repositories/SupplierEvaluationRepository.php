<?php
class SupplierEvaluationRepository
{
    public function __construct(private Database $db)
    {
    }

    public function bySupplierAndDate(?int $supplierId = null, string $from = '', string $to = ''): array
    {
        $sql = 'SELECT ep.*, s.name AS supplier_name, s.nit AS supplier_nit, s.service AS supplier_service,
                       u.name AS evaluator_name
                FROM supplier_evaluations ep
                INNER JOIN suppliers s ON s.id = ep.supplier_id
                INNER JOIN users u ON u.id = ep.evaluator_user_id
                WHERE 1 = 1';
        $params = [];

        if ($supplierId) {
            $sql .= ' AND ep.supplier_id = ?';
            $params[] = $supplierId;
        }

        if ($from !== '') {
            $sql .= ' AND DATE(ep.evaluation_date) >= ?';
            $params[] = $from;
        }

        if ($to !== '') {
            $sql .= ' AND DATE(ep.evaluation_date) <= ?';
            $params[] = $to;
        }

        $sql .= ' ORDER BY ep.evaluation_date DESC, ep.id DESC';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT ep.*, s.name AS supplier_name, s.nit AS supplier_nit, s.service AS supplier_service, s.email AS supplier_email,
                                                  u.name AS evaluator_name, u.email AS evaluator_email
                                           FROM supplier_evaluations ep
                                           INNER JOIN suppliers s ON s.id = ep.supplier_id
                                           INNER JOIN users u ON u.id = ep.evaluator_user_id
                                           WHERE ep.id = ?
                                           LIMIT 1');
        $stmt->execute([$id]);
        $evaluation = $stmt->fetch();
        if (!$evaluation) {
            return null;
        }

        $detailsStmt = $this->db->pdo()->prepare('SELECT criterion_code, criterion_name, option_key, option_label, score, notes
                                                  FROM supplier_evaluation_details
                                                  WHERE evaluation_id = ?
                                                  ORDER BY id');
        $detailsStmt->execute([$id]);
        $evaluation['details'] = $detailsStmt->fetchAll();

        return $evaluation;
    }


    public function attachPdf(int $evaluationId, string $pdfPath): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE supplier_evaluations SET pdf_path = ? WHERE id = ?');
        $stmt->execute([$pdfPath, $evaluationId]);
    }

    public function create(array $header, array $details): int
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('INSERT INTO supplier_evaluations
                (supplier_id, evaluator_user_id, evaluation_date, total_score, status_label, observations, pdf_path, created_at)
                VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $header['supplier_id'],
                $header['evaluator_user_id'],
                $header['total_score'],
                $header['status_label'],
                $header['observations'] ?? null,
                $header['pdf_path'] ?? null,
            ]);

            $evaluationId = (int)$pdo->lastInsertId();

            $detailStmt = $pdo->prepare('INSERT INTO supplier_evaluation_details
                (evaluation_id, criterion_code, criterion_name, option_key, option_label, score, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)');

            foreach ($details as $detail) {
                $detailStmt->execute([
                    $evaluationId,
                    $detail['criterion_code'],
                    $detail['criterion_name'],
                    $detail['option_key'],
                    $detail['option_label'],
                    $detail['score'],
                    $detail['notes'] ?? null,
                ]);
            }

            $pdo->commit();
            return $evaluationId;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
