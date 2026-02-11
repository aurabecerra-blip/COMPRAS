<?php
class SupplierSelectionController
{
    public function __construct(
        private PurchaseRequestRepository $purchaseRequests,
        private SupplierRepository $suppliers,
        private SupplierSelectionRepository $repository,
        private SupplierSelectionService $service,
        private Flash $flash,
        private AuditLogger $audit,
        private Auth $auth,
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
        if (($pr['status'] ?? '') !== 'APROBADA') {
            $this->unprocessable('La Evaluación de Selección de Proveedor solo puede iniciarse con una Solicitud de Compra en estado Aprobada.');
        }

        $process = $this->repository->getOrCreateProcess($purchaseRequestId, (int)$this->auth->user()['id']);
        $quotations = $this->repository->quotationsByProcess((int)$process['id']);
        $scores = $this->repository->scoresByProcess((int)$process['id']);
        $suppliers = $this->suppliers->all();

        include __DIR__ . '/../views/supplier_selection/index.php';
    }

    public function storeQuotation(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr || ($pr['status'] ?? '') !== 'APROBADA') {
            $this->unprocessable('Solo se pueden registrar cotizaciones para solicitudes aprobadas.');
        }
        $process = $this->repository->getOrCreateProcess($purchaseRequestId, (int)$this->auth->user()['id']);

        $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'xlsx', 'xls'];
        $files = $this->normalizeFilesArray($_FILES['evidence_files'] ?? null);
        if (count($files) === 0) {
            $this->unprocessable('Debe adjuntar al menos un archivo de cotización (PDF, Excel o imagen).');
        }

        $dir = __DIR__ . '/../../public/storage/selection_quotes/' . $purchaseRequestId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $storedFiles = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $this->unprocessable('Extensión no permitida. Use PDF, imagen o Excel.');
            }

            $size = (int)($file['size'] ?? 0);
            if ($size > 10 * 1024 * 1024) {
                $this->unprocessable('Cada archivo no debe exceder 10MB.');
            }

            $filename = uniqid('cot_', true) . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
                $this->unprocessable('No fue posible guardar uno de los archivos adjuntos.');
            }

            $storedFiles[] = [
                'file_path' => '/storage/selection_quotes/' . $purchaseRequestId . '/' . $filename,
                'original_name' => (string)$file['name'],
                'mime_type' => (string)($file['type'] ?? ''),
                'file_size' => $size,
            ];
        }

        $primaryFile = $storedFiles[0];

        try {
            $quotationId = $this->repository->addQuotation((int)$process['id'], [
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'purchase_request_id' => $purchaseRequestId,
                'quote_number' => trim((string)($_POST['quote_number'] ?? '')) ?: null,
                'quotation_date' => trim((string)($_POST['quotation_date'] ?? '')) ?: date('Y-m-d'),
                'total_value' => (float)($_POST['total_value'] ?? 0),
                'currency' => trim((string)($_POST['currency'] ?? 'COP')) ?: 'COP',
                'delivery_term_days' => (int)($_POST['delivery_term_days'] ?? 0),
                'payment_terms' => trim((string)($_POST['payment_terms'] ?? '')),
                'warranty' => trim((string)($_POST['warranty'] ?? '')),
                'technical_compliance' => (string)($_POST['technical_compliance'] ?? 'CUMPLE'),
                'observations' => trim((string)($_POST['observations'] ?? '')) ?: null,
                'evidence_file_path' => $primaryFile['file_path'],
                'evidence_original_name' => $primaryFile['original_name'],
                'mime_type' => $primaryFile['mime_type'],
                'file_size' => $primaryFile['file_size'],
                'uploaded_by' => (int)$this->auth->user()['id'],
            ]);

            foreach ($storedFiles as $storedFile) {
                $this->repository->addQuotationFile($quotationId, [
                    'file_path' => $storedFile['file_path'],
                    'original_name' => $storedFile['original_name'],
                    'mime_type' => $storedFile['mime_type'],
                    'file_size' => $storedFile['file_size'],
                    'uploaded_by' => (int)$this->auth->user()['id'],
                ]);
            }
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'uq_supplier_quotations_process_supplier')) {
                $this->unprocessable('No se permite más de una cotización por proveedor en este proceso.');
            }
            throw $e;
        }

        $this->repository->updateProcessStatus((int)$process['id'], ['status' => 'BORRADOR']);
        $this->flash->add('success', 'Cotización registrada como evidencia.');
        header('Location: ' . route_to('supplier_selection', ['id' => $purchaseRequestId]));
    }

    public function decide(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr || ($pr['status'] ?? '') !== 'APROBADA') {
            $this->unprocessable('Solo se puede finalizar la evaluación para solicitudes aprobadas.', route_to('purchase_requests'));
        }
        $process = $this->repository->getOrCreateProcess($purchaseRequestId, (int)$this->auth->user()['id']);
        $quotations = $this->repository->quotationsByProcess((int)$process['id']);

        try {
            $scores = $this->service->score($quotations);
            $winner = $this->service->resolveWinner($scores, (int)($_POST['winner_supplier_id'] ?? 0), trim((string)($_POST['winner_justification'] ?? '')));
        } catch (Throwable $e) {
            $this->unprocessable($e->getMessage(), route_to('supplier_selection', ['id' => $purchaseRequestId]));
        }

        foreach ($scores as &$row) {
            $row['is_winner'] = ((int)$row['supplier_id'] === (int)$winner['winner_supplier_id']);
        }
        unset($row);

        $this->repository->replaceScores((int)$process['id'], $scores);

        $suppliersById = [];
        foreach ($this->suppliers->all() as $supplier) {
            $suppliersById[(int)$supplier['id']] = $supplier['name'];
        }

        $scoresForPdf = array_map(function (array $row) use ($suppliersById) {
            $row['supplier_name'] = $suppliersById[(int)$row['supplier_id']] ?? ('Proveedor #' . $row['supplier_id']);
            return $row;
        }, $scores);

        $pdfPath = $this->service->buildActPdf([
            'purchase_request' => $pr ?: ['id' => $purchaseRequestId, 'title' => 'N/D'],
            'quotations' => $quotations,
            'scores' => $scoresForPdf,
            'winner_name' => $suppliersById[(int)$winner['winner_supplier_id']] ?? 'N/D',
            'winner_justification' => $winner['justification'],
            'observations' => trim((string)($_POST['observations'] ?? '')),
        ]);

        $this->repository->updateProcessStatus((int)$process['id'], [
            'status' => 'FINALIZADA',
            'winner_supplier_id' => $winner['winner_supplier_id'],
            'winner_justification' => $winner['justification'],
            'observations' => trim((string)($_POST['observations'] ?? '')) ?: null,
            'selection_pdf_path' => $pdfPath,
            'selected_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log((int)$this->auth->user()['id'], 'supplier_selection_decide', [
            'purchase_request_id' => $purchaseRequestId,
            'winner_supplier_id' => $winner['winner_supplier_id'],
            'scores' => $scores,
        ]);

        $this->flash->add('success', 'Selección final registrada. Se generó el Acta PDF.');
        header('Location: ' . route_to('supplier_selection', ['id' => $purchaseRequestId]));
    }

    public function pdf(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider', 'aprobador']);

        $purchaseRequestId = (int)($_GET['id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr) {
            http_response_code(404);
            echo 'Solicitud no encontrada';
            return;
        }

        $process = $this->repository->getOrCreateProcess($purchaseRequestId, (int)$this->auth->user()['id']);
        if (empty($process['selection_pdf_path'])) {
            http_response_code(404);
            echo 'PDF no disponible';
            return;
        }

        $absolutePath = __DIR__ . '/../../public' . $process['selection_pdf_path'];
        if (!is_file($absolutePath)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="evaluacion_seleccion_' . $purchaseRequestId . '.pdf"');
        readfile($absolutePath);
    }

    private function normalizeFilesArray(mixed $files): array
    {
        if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $normalized = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }
            $normalized[] = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'size' => $files['size'][$i] ?? 0,
            ];
        }

        return $normalized;
    }

    private function unprocessable(string $message, ?string $fallback = null): void
    {
        http_response_code(422);
        $this->flash->add('danger', $message);
        header('Location: ' . ($fallback ?: ($_SERVER['HTTP_REFERER'] ?? route_to('purchase_requests'))));
        exit;
    }
}
