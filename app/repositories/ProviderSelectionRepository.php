<?php
class ProviderSelectionRepository
{
    public function __construct(private Database $db)
    {
    }

    public function getOrCreateEvaluation(int $purchaseRequestId): array
    {
        $existing = $this->findByPurchaseRequest($purchaseRequestId);
        if ($existing) {
            return $existing;
        }

        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO provider_selection_evaluations (purchase_request_id, status, created_at, updated_at)
             VALUES (?, "DRAFT", NOW(), NOW())'
        );
        $stmt->execute([$purchaseRequestId]);

        return $this->find((int)$this->db->pdo()->lastInsertId()) ?? [];
    }

    public function findByPurchaseRequest(int $purchaseRequestId): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM provider_selection_evaluations WHERE purchase_request_id = ? LIMIT 1');
        $stmt->execute([$purchaseRequestId]);
        $evaluation = $stmt->fetch();
        if (!$evaluation) {
            return null;
        }

        $evaluation['scores'] = $this->scores((int)$evaluation['id']);
        return $evaluation;
    }

    public function find(int $evaluationId): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM provider_selection_evaluations WHERE id = ? LIMIT 1');
        $stmt->execute([$evaluationId]);
        $evaluation = $stmt->fetch();
        if (!$evaluation) {
            return null;
        }

        $evaluation['scores'] = $this->scores($evaluationId);
        return $evaluation;
    }

    public function scores(int $evaluationId): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT s.*, p.name AS provider_name
             FROM provider_selection_scores s
             INNER JOIN suppliers p ON p.id = s.provider_id
             WHERE s.evaluation_id = ?
             ORDER BY s.total_score DESC, s.precios_score DESC, s.provider_id ASC'
        );
        $stmt->execute([$evaluationId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['criterio_detalle'] = json_decode($row['criterio_detalle_json'] ?? '{}', true) ?: [];
        }
        return $rows;
    }

    public function upsertScore(int $evaluationId, int $providerId, array $scoreData, array $detail, ?string $observations = null): void
    {
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO provider_selection_scores
            (evaluation_id, provider_id, experiencia_score, forma_pago_score, entrega_score, descuento_score, certificaciones_score, precios_score, total_score, criterio_detalle_json, observations, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            experiencia_score = VALUES(experiencia_score),
            forma_pago_score = VALUES(forma_pago_score),
            entrega_score = VALUES(entrega_score),
            descuento_score = VALUES(descuento_score),
            certificaciones_score = VALUES(certificaciones_score),
            precios_score = VALUES(precios_score),
            total_score = VALUES(total_score),
            criterio_detalle_json = VALUES(criterio_detalle_json),
            observations = VALUES(observations),
            updated_at = NOW()'
        );
        $stmt->execute([
            $evaluationId,
            $providerId,
            $scoreData['experiencia_score'],
            $scoreData['forma_pago_score'],
            $scoreData['entrega_score'],
            $scoreData['descuento_score'],
            $scoreData['certificaciones_score'],
            $scoreData['precios_score'],
            $scoreData['total_score'],
            json_encode($detail, JSON_UNESCAPED_UNICODE),
            $observations,
        ]);

        $this->db->pdo()->prepare('UPDATE provider_selection_evaluations SET updated_at = NOW() WHERE id = ?')->execute([$evaluationId]);
    }

    public function closeEvaluation(int $evaluationId, array $closeData): void
    {
        $stmt = $this->db->pdo()->prepare(
            'UPDATE provider_selection_evaluations
             SET status = "CLOSED", closed_at = NOW(), closed_by = ?, winner_provider_id = ?, tie_break_reason = ?, observations = ?, pdf_path = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $closeData['closed_by'],
            $closeData['winner_provider_id'],
            $closeData['tie_break_reason'],
            $closeData['observations'],
            $closeData['pdf_path'],
            $evaluationId,
        ]);
    }

    public function updateDraftObservations(int $evaluationId, ?string $observations): void
    {
        $stmt = $this->db->pdo()->prepare('UPDATE provider_selection_evaluations SET observations = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$observations, $evaluationId]);
    }
}
