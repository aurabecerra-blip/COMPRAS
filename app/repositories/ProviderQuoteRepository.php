<?php
class ProviderQuoteRepository
{
    public function __construct(private Database $db)
    {
    }

    public function forPurchaseRequest(int $purchaseRequestId): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT q.*, s.name AS provider_name,
                (SELECT COUNT(*) FROM provider_quote_files f WHERE f.quote_id = q.id) AS file_count
             FROM provider_quotes q
             INNER JOIN suppliers s ON s.id = q.provider_id
             WHERE q.purchase_request_id = ?
             ORDER BY q.created_at DESC'
        );
        $stmt->execute([$purchaseRequestId]);
        return $stmt->fetchAll();
    }

    public function create(int $purchaseRequestId, array $data): int
    {
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO provider_quotes
            (purchase_request_id, provider_id, tipo_compra, valor, moneda, plazo_entrega_dias, forma_pago, experiencia, entrega, entrega_na_result, descuento, certificaciones, recotizacion, notas, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $purchaseRequestId,
            $data['provider_id'],
            $data['tipo_compra'],
            $data['valor'],
            $data['moneda'] ?? 'COP',
            $data['plazo_entrega_dias'],
            $data['forma_pago'],
            $data['experiencia'],
            $data['entrega'],
            $data['entrega_na_result'],
            $data['descuento'],
            $data['certificaciones'],
            $data['recotizacion'] ? 1 : 0,
            $data['notas'] ?? null,
            $data['created_by'],
        ]);

        return (int)$this->db->pdo()->lastInsertId();
    }

    public function update(int $quoteId, array $data): void
    {
        $stmt = $this->db->pdo()->prepare(
            'UPDATE provider_quotes
             SET tipo_compra = ?, valor = ?, moneda = ?, plazo_entrega_dias = ?, forma_pago = ?, experiencia = ?, entrega = ?, entrega_na_result = ?, descuento = ?, certificaciones = ?, recotizacion = ?, notas = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $data['tipo_compra'],
            $data['valor'],
            $data['moneda'] ?? 'COP',
            $data['plazo_entrega_dias'],
            $data['forma_pago'],
            $data['experiencia'],
            $data['entrega'],
            $data['entrega_na_result'],
            $data['descuento'],
            $data['certificaciones'],
            $data['recotizacion'] ? 1 : 0,
            $data['notas'] ?? null,
            $quoteId,
        ]);
    }

    public function latestQuotesByProvider(int $purchaseRequestId): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT q.*, s.name AS provider_name
             FROM provider_quotes q
             INNER JOIN suppliers s ON s.id = q.provider_id
             INNER JOIN (
                SELECT provider_id, MAX(id) AS latest_quote_id
                FROM provider_quotes
                WHERE purchase_request_id = ?
                GROUP BY provider_id
             ) latest ON latest.latest_quote_id = q.id
             WHERE q.purchase_request_id = ?
             ORDER BY q.valor ASC, q.created_at DESC'
        );
        $stmt->execute([$purchaseRequestId, $purchaseRequestId]);
        return $stmt->fetchAll();
    }

    public function find(int $quoteId): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM provider_quotes WHERE id = ?');
        $stmt->execute([$quoteId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsNonRecotizationProvider(int $purchaseRequestId, int $providerId, ?int $excludeQuoteId = null): bool
    {
        $sql = 'SELECT id FROM provider_quotes WHERE purchase_request_id = ? AND provider_id = ? AND recotizacion = 0';
        $params = [$purchaseRequestId, $providerId];
        if ($excludeQuoteId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeQuoteId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function countDistinctProvidersForMinimum(int $purchaseRequestId): int
    {
        $stmt = $this->db->pdo()->prepare('SELECT COUNT(DISTINCT provider_id) AS total FROM provider_quotes WHERE purchase_request_id = ? AND recotizacion = 0');
        $stmt->execute([$purchaseRequestId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function filesByPurchaseRequest(int $purchaseRequestId): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT f.*, q.provider_id, q.purchase_request_id, s.name AS provider_name
             FROM provider_quote_files f
             INNER JOIN provider_quotes q ON q.id = f.quote_id
             INNER JOIN suppliers s ON s.id = q.provider_id
             WHERE q.purchase_request_id = ?
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$purchaseRequestId]);
        return $stmt->fetchAll();
    }

    public function addFile(int $quoteId, array $file): void
    {
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO provider_quote_files
            (quote_id, file_path, original_name, mime_type, file_size, uploaded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $quoteId,
            $file['file_path'],
            $file['original_name'],
            $file['mime_type'],
            $file['file_size'],
            $file['uploaded_by'],
        ]);
    }
}
