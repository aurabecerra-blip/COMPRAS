<?php
class ProviderQuoteController
{
    public function __construct(
        private ProviderQuoteRepository $quotes,
        private PurchaseRequestRepository $purchaseRequests,
        private SupplierRepository $suppliers,
        private ProviderSelectionRepository $selections,
        private AuditLogger $audit,
        private Auth $auth,
        private Flash $flash,
        private AuthMiddleware $authMiddleware
    ) {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider']);

        $purchaseRequestId = (int)($_GET['id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr) {
            http_response_code(404);
            echo 'Solicitud no encontrada';
            return;
        }

        $providers = $this->suppliers->all();
        $quotes = $this->quotes->forPurchaseRequest($purchaseRequestId);
        $latestQuotesByProvider = $this->quotes->latestQuotesByProvider($purchaseRequestId);
        $files = $this->quotes->filesByPurchaseRequest($purchaseRequestId);
        $evaluation = $this->selections->getOrCreateEvaluation($purchaseRequestId);

        include __DIR__ . '/../views/provider_selection/index.php';
    }

    public function store(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $providerName = trim((string)($_POST['provider_name'] ?? ''));
        try {
            $providerId = (int)$this->suppliers->findOrCreateByName($providerName);
        } catch (Throwable $e) {
            $this->unprocessable($e->getMessage());
            return;
        }
        $isRecotizacion = isset($_POST['recotizacion']) && $_POST['recotizacion'] === '1';

        if ($purchaseRequestId <= 0) {
            $this->unprocessable('Proveedor y solicitud son obligatorios.');
        }

        if (!$isRecotizacion && $this->quotes->existsNonRecotizationProvider($purchaseRequestId, $providerId)) {
            $this->unprocessable('No puedes repetir proveedor para el mínimo requerido de 3 cotizaciones (usa re-cotización).');
        }

        $tipoCompra = (string)($_POST['tipo_compra'] ?? '');
        $formaPago = (string)($_POST['forma_pago'] ?? '');
        if (!in_array($tipoCompra, ['BIENES', 'SERVICIOS', 'SERVICIOS_TECNICOS'], true)) {
            $this->unprocessable('Tipo de compra inválido.');
        }
        if (!in_array($formaPago, ['CONTADO', 'CREDICONTADO', 'CREDITO_30_MAS', 'NA'], true)) {
            $this->unprocessable('Forma de pago inválida.');
        }

        $experiencia = (string)($_POST['experiencia'] ?? '');
        if (!in_array($experiencia, ['LT2', '2TO5', 'GT5'], true)) {
            $this->unprocessable('Experiencia inválida.');
        }

        $entrega = (string)($_POST['entrega'] ?? '');
        $entregaNaResult = (string)($_POST['entrega_na_result'] ?? 'NO_CUMPLE');
        if (!in_array($entrega, ['MAYOR_10', 'IGUAL_10', 'MENOR_5', 'NA'], true)) {
            $this->unprocessable('Condición de entrega inválida.');
        }
        if (!in_array($entregaNaResult, ['CUMPLE', 'NO_CUMPLE'], true)) {
            $this->unprocessable('Resultado de cronograma inválido.');
        }

        $descuento = (string)($_POST['descuento'] ?? 'NO');
        if (!in_array($descuento, ['SI', 'NO'], true)) {
            $this->unprocessable('Descuento inválido.');
        }

        $certificaciones = (string)($_POST['certificaciones'] ?? 'NINGUNA');
        if (!in_array($certificaciones, ['NINGUNA', 'UNA', 'DOS_MAS'], true)) {
            $this->unprocessable('Certificaciones inválidas.');
        }

        $quoteId = $this->quotes->create($purchaseRequestId, [
            'provider_id' => $providerId,
            'tipo_compra' => $tipoCompra,
            'valor' => (float)($_POST['valor'] ?? 0),
            'moneda' => trim((string)($_POST['moneda'] ?? 'COP')) ?: 'COP',
            'plazo_entrega_dias' => (int)($_POST['plazo_entrega_dias'] ?? 0),
            'forma_pago' => $formaPago,
            'experiencia' => $experiencia,
            'entrega' => $entrega,
            'entrega_na_result' => $entregaNaResult,
            'descuento' => $descuento,
            'certificaciones' => $certificaciones,
            'recotizacion' => $isRecotizacion,
            'notas' => trim((string)($_POST['notas'] ?? '')),
            'created_by' => (int)$this->auth->user()['id'],
        ]);

        $this->storeFiles($purchaseRequestId, $providerId, $quoteId);

        $evaluation = $this->selections->getOrCreateEvaluation($purchaseRequestId);
        $autoScoring = new ProviderSelectionScoringService();
        $latestQuotes = $this->quotes->latestQuotesByProvider($purchaseRequestId);
        $scores = $autoScoring->buildScoresFromQuotes($latestQuotes);
        foreach ($scores as $score) {
            $this->selections->upsertScore((int)$evaluation['id'], (int)$score['provider_id'], $score, $score['detail'], null);
        }

        $this->audit->log((int)$this->auth->user()['id'], 'provider_quote_create', [
            'purchase_request_id' => $purchaseRequestId,
            'provider_id' => $providerId,
            'quote_id' => $quoteId,
        ]);

        $this->flash->add('success', 'Cotización registrada correctamente.');
        header('Location: ' . route_to('provider_selection', ['id' => $purchaseRequestId]));
    }

    private function storeFiles(int $purchaseRequestId, int $providerId, int $quoteId): void
    {
        if (empty($_FILES['quote_files']['name']) || !is_array($_FILES['quote_files']['name'])) {
            return;
        }

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx'];
        $allowedMime = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream',
        ];

        $baseDir = __DIR__ . '/../../public/storage/cotizaciones/' . $purchaseRequestId . '/' . $providerId;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $fileCount = count($_FILES['quote_files']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $name = $_FILES['quote_files']['name'][$i] ?? '';
            $tmp = $_FILES['quote_files']['tmp_name'][$i] ?? '';
            $error = $_FILES['quote_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $size = (int)($_FILES['quote_files']['size'][$i] ?? 0);
            $mime = (string)($_FILES['quote_files']['type'][$i] ?? '');
            if ($error !== UPLOAD_ERR_OK || $tmp === '') {
                continue;
            }
            if ($size > 10 * 1024 * 1024) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
                continue;
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($name, PATHINFO_FILENAME)) ?: 'archivo';
            $fileName = uniqid('cot_', true) . '_' . $safeBase . '.' . $ext;
            $dest = $baseDir . '/' . $fileName;
            if (move_uploaded_file($tmp, $dest)) {
                $this->quotes->addFile($quoteId, [
                    'file_path' => '/storage/cotizaciones/' . $purchaseRequestId . '/' . $providerId . '/' . $fileName,
                    'original_name' => $name,
                    'mime_type' => $mime,
                    'file_size' => $size,
                    'uploaded_by' => (int)$this->auth->user()['id'],
                ]);
            }
        }
    }

    private function unprocessable(string $message): void
    {
        http_response_code(422);
        $this->flash->add('danger', $message);
        $fallback = $_SERVER['HTTP_REFERER'] ?? route_to('purchase_requests');
        header('Location: ' . $fallback);
        exit;
    }
}
