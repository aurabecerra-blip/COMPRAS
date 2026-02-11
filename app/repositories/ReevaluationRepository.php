<?php
class ReevaluationRepository
{
    public function __construct(private Database $db)
    {
    }

    public function create(array $header, array $items): int
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO provider_reevaluations
                (provider_id, provider_name, provider_nit, service_provided, evaluation_date, evaluator_user_id, observations, total_score, pdf_path, email_status, email_error, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([
                $header['provider_id'],
                $header['provider_name'],
                $header['provider_nit'] ?? null,
                $header['service_provided'] ?? null,
                $header['evaluation_date'],
                $header['evaluator_user_id'],
                $header['observations'] ?? null,
                $header['total_score'],
                $header['pdf_path'] ?? null,
                $header['email_status'] ?? 'pending',
                $header['email_error'] ?? null,
            ]);
            $id = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO provider_reevaluation_items
                (reevaluation_id, criterion_code, criterion_name, selected_option, selected_label, extra_value, item_score, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            foreach ($items as $item) {
                $itemStmt->execute([
                    $id,
                    $item['criterion_code'],
                    $item['criterion_name'],
                    $item['selected_option'],
                    $item['selected_label'],
                    $item['extra_value'] ?? null,
                    $item['item_score'],
                ]);
            }

            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updatePdf(int $id, string $pdfPath): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE provider_reevaluations SET pdf_path = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$pdfPath, $id]);
    }

    public function updateEmailStatus(int $id, string $status, ?string $error): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE provider_reevaluations SET email_status = ?, email_error = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $error, $id]);
    }

    public function findWithItems(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT r.*, u.name AS evaluator_name, s.email AS provider_email
            FROM provider_reevaluations r
            INNER JOIN users u ON u.id = r.evaluator_user_id
            INNER JOIN suppliers s ON s.id = r.provider_id
            WHERE r.id = ?
            LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $itemStmt = $this->db->pdo()->prepare('SELECT * FROM provider_reevaluation_items WHERE reevaluation_id = ? ORDER BY id');
        $itemStmt->execute([$id]);
        $row['items'] = $itemStmt->fetchAll();
        return $row;
    }

    public function listAll(?int $providerId = null): array
    {
        $sql = 'SELECT r.*, u.name AS evaluator_name
                FROM provider_reevaluations r
                INNER JOIN users u ON u.id = r.evaluator_user_id
                WHERE 1 = 1';
        $params = [];
        if ($providerId) {
            $sql .= ' AND r.provider_id = ?';
            $params[] = $providerId;
        }
        $sql .= ' ORDER BY r.evaluation_date DESC, r.id DESC';
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
