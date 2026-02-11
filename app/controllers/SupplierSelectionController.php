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
        $this->auth->requireRole(['compras', 'administrador', 'lider', 'aprobador']);

        $purchaseRequestId = (int)($_GET['id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr) {
            http_response_code(404);
            echo 'Solicitud no encontrada';
            return;
        }
        if (($pr['status'] ?? '') !== 'APROBADA') {
            $this->unprocessable('El módulo de selección se habilita únicamente cuando la solicitud está APROBADA.');
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
        $this->auth->requireRole(['compras', 'administrador', 'lider', 'aprobador']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr || ($pr['status'] ?? '') !== 'APROBADA') {
            $this->unprocessable('Solo se pueden registrar cotizaciones para solicitudes aprobadas.');
        }

        $process = $this->repository->getOrCreateProcess($purchaseRequestId, (int)$this->auth->user()['id']);

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $valorSubtotal = (float)($_POST['valor_subtotal'] ?? 0);
        $valorTotal = (float)($_POST['valor_total'] ?? 0);
        $deliveryDays = (int)($_POST['delivery_term_days'] ?? 0);
        $paymentTerms = trim((string)($_POST['payment_terms'] ?? ''));
        $warranty = trim((string)($_POST['warranty'] ?? ''));
        $evaluationPayment = (string)($_POST['evaluacion_pago'] ?? 'ACEPTABLES');
        $evaluationPostSale = (string)($_POST['evaluacion_postventa'] ?? 'CUMPLE_PARCIAL');
        $ofreceDescuento = isset($_POST['ofrece_descuento']) ? 1 : 0;
        $tipoDescuento = $ofreceDescuento ? (string)($_POST['tipo_descuento'] ?? '') : null;
        $descuentoValor = $ofreceDescuento ? (float)($_POST['descuento_valor'] ?? 0) : null;
        $experienciaAnios = (int)($_POST['experiencia_anios'] ?? -1);
        $certTec = isset($_POST['certificaciones_tecnicas']) ? 1 : 0;
        $certCom = isset($_POST['certificaciones_comerciales']) ? 1 : 0;
        $listaCert = trim((string)($_POST['lista_certificaciones'] ?? ''));

        if ($supplierId <= 0 || $valorSubtotal < 0 || $valorTotal <= 0 || $deliveryDays <= 0 || $paymentTerms === '' || $warranty === '') {
            $this->unprocessable('Complete todos los campos obligatorios de la cotización.');
        }
        if (!in_array($evaluationPayment, ['MUY_FAVORABLES', 'ACEPTABLES', 'POCO_FAVORABLES'], true)) {
            $this->unprocessable('Seleccione una opción válida para condiciones de pago.');
        }
        if (!in_array($evaluationPostSale, ['CUMPLE_TOTAL', 'CUMPLE_PARCIAL', 'NO_CUMPLE'], true)) {
            $this->unprocessable('Seleccione una opción válida para garantía / postventa.');
        }
        if ($ofreceDescuento === 1) {
            if (!in_array($tipoDescuento, ['PORCENTAJE', 'VALOR'], true) || $descuentoValor === null || $descuentoValor <= 0) {
                $this->unprocessable('Si ofrece descuento, tipo_descuento y descuento_valor son obligatorios.');
            }
        }
        if ($experienciaAnios < 0) {
            $this->unprocessable('experiencia_anios es obligatorio y debe ser >= 0.');
        }
        if (($certTec === 1 || $certCom === 1) && $listaCert === '') {
            $this->unprocessable('Si marca certificaciones, debe diligenciar lista_certificaciones.');
        }

        $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'xlsx', 'xls'];
        $cotFile = $this->singleUpload($_FILES['archivo_cotizacion'] ?? null, true, $allowedExt, $purchaseRequestId, 'cotizacion');
        $expFile = $this->singleUpload($_FILES['archivo_soporte_experiencia'] ?? null, false, $allowedExt, $purchaseRequestId, 'experiencia');
        $certFileRequired = ($certTec === 1 || $certCom === 1);
        $certFile = $this->singleUpload($_FILES['archivo_certificaciones'] ?? null, $certFileRequired, $allowedExt, $purchaseRequestId, 'certificaciones');

        try {
            $quotationId = $this->repository->addQuotation((int)$process['id'], [
                'supplier_id' => $supplierId,
                'purchase_request_id' => $purchaseRequestId,
                'quote_number' => trim((string)($_POST['quote_number'] ?? '')) ?: null,
                'quotation_date' => trim((string)($_POST['quotation_date'] ?? '')) ?: date('Y-m-d'),
                'valor_subtotal' => $valorSubtotal,
                'valor_total' => $valorTotal,
                'currency' => trim((string)($_POST['currency'] ?? 'COP')) ?: 'COP',
                'delivery_term_days' => $deliveryDays,
                'payment_terms' => $paymentTerms,
                'evaluacion_pago' => $evaluationPayment,
                'warranty' => $warranty,
                'evaluacion_postventa' => $evaluationPostSale,
                'observations' => trim((string)($_POST['observations'] ?? '')) ?: null,
                'ofrece_descuento' => $ofreceDescuento,
                'tipo_descuento' => $tipoDescuento,
                'descuento_valor' => $descuentoValor,
                'experiencia_anios' => $experienciaAnios,
                'certificaciones_tecnicas' => $certTec,
                'certificaciones_comerciales' => $certCom,
                'lista_certificaciones' => $listaCert !== '' ? $listaCert : null,
                'archivo_cotizacion_url' => $cotFile['file_path'],
                'archivo_soporte_experiencia_url' => $expFile['file_path'] ?? null,
                'archivo_certificaciones_url' => $certFile['file_path'] ?? null,
                'evidence_original_name' => $cotFile['original_name'],
                'mime_type' => $cotFile['mime_type'] ?? null,
                'file_size' => $cotFile['file_size'] ?? null,
                'uploaded_by' => (int)$this->auth->user()['id'],
            ]);

            foreach ([$cotFile, $expFile, $certFile] as $storedFile) {
                if (!$storedFile) {
                    continue;
                }
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
                $this->unprocessable('No se permite más de una cotización por proveedor en el mismo proceso.');
            }
            throw $e;
        }

        $distinct = $this->repository->countDistinctSuppliers((int)$process['id']);
        $status = $distinct >= 3 ? 'EN_EVALUACION' : 'BORRADOR';
        $this->repository->updateProcessStatus((int)$process['id'], ['status' => $status]);

        $this->flash->add('success', 'Cotización registrada correctamente.');
        header('Location: ' . route_to('supplier_selection', ['id' => $purchaseRequestId]));
    }

    public function decide(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['aprobador', 'lider', 'administrador']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $pr = $this->purchaseRequests->find($purchaseRequestId);
        if (!$pr || ($pr['status'] ?? '') !== 'APROBADA') {
            $this->unprocessable('Solo puede cerrar la selección sobre solicitudes aprobadas.', route_to('purchase_requests'));
        }

        $process = $this->repository->getOrCreateProcess($purchaseRequestId, (int)$this->auth->user()['id']);
        $quotations = $this->repository->quotationsByProcess((int)$process['id']);

        try {
            $scores = $this->service->score($quotations);
            $winner = $this->service->resolveWinner(
                $scores,
                (int)($_POST['winner_supplier_id'] ?? 0),
                trim((string)($_POST['winner_justification'] ?? ''))
            );
        } catch (Throwable $e) {
            $this->unprocessable($e->getMessage(), route_to('supplier_selection', ['id' => $purchaseRequestId]));
        }

        foreach ($scores as &$score) {
            $score['is_winner'] = ((int)$score['supplier_id'] === (int)$winner['winner_supplier_id']);
        }
        unset($score);

        $this->repository->replaceScores((int)$process['id'], $scores);

        $suppliersById = [];
        foreach ($this->suppliers->all() as $supplier) {
            $suppliersById[(int)$supplier['id']] = $supplier['name'];
        }

        $actaPdfPath = null;
        try {
            $actaPdfPath = $this->service->buildActPdf([
                'purchase_request' => $pr,
                'quotations' => $quotations,
                'scores' => $scores,
                'winner_name' => $suppliersById[(int)$winner['winner_supplier_id']] ?? 'N/D',
                'winner_justification' => $winner['justification'],
            ]);
        } catch (Throwable $e) {
            error_log('[supplier_selection_pdf_error] PR ' . $purchaseRequestId . ': ' . $e->getMessage());
            $this->audit->log((int)$this->auth->user()['id'], 'supplier_selection_pdf_error', [
                'purchase_request_id' => $purchaseRequestId,
                'error' => $e->getMessage(),
            ]);
            $this->flash->add('warning', 'La selección se guardó, pero falló la generación del Acta PDF. Revise logs.');
        }

        $this->repository->updateProcessStatus((int)$process['id'], [
            'status' => 'SELECCIONADO',
            'winner_supplier_id' => $winner['winner_supplier_id'],
            'winner_justification' => $winner['justification'],
            'observations' => trim((string)($_POST['observations'] ?? '')) ?: null,
            'selection_pdf_path' => $actaPdfPath,
            'selected_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log((int)$this->auth->user()['id'], 'supplier_selection_decide', [
            'purchase_request_id' => $purchaseRequestId,
            'winner_supplier_id' => $winner['winner_supplier_id'],
            'scores' => array_map(fn($row) => [
                'supplier_id' => $row['supplier_id'],
                'total_score' => $row['total_score'],
                'rank_position' => $row['rank_position'],
            ], $scores),
        ]);

        if ($actaPdfPath) {
            $this->flash->add('success', 'Selección registrada y Acta PDF generada correctamente.');
        } else {
            $this->flash->add('success', 'Selección registrada sin PDF (ver advertencias).');
        }
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
        if (empty($process['acta_pdf_url'])) {
            http_response_code(404);
            echo 'Acta PDF no disponible';
            return;
        }

        $absolutePath = __DIR__ . '/../../public' . $process['acta_pdf_url'];
        if (!is_file($absolutePath)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="acta_seleccion_' . $purchaseRequestId . '.pdf"');
        readfile($absolutePath);
    }

    private function singleUpload(mixed $file, bool $required, array $allowedExt, int $purchaseRequestId, string $prefix): ?array
    {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $this->unprocessable('Debe adjuntar el archivo obligatorio: ' . $prefix . '.');
            }
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->unprocessable('Error al cargar archivo de ' . $prefix . '.');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $this->unprocessable('Extensión no permitida para ' . $prefix . '. Use PDF, imagen o Excel.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $this->unprocessable('El archivo de ' . $prefix . ' debe ser >0 y no superar 10MB.');
        }

        $dir = __DIR__ . '/../../public/storage/selection_quotes/' . $purchaseRequestId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = uniqid($prefix . '_', true) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
            $this->unprocessable('No fue posible guardar el archivo de ' . $prefix . '.');
        }

        return [
            'file_path' => '/storage/selection_quotes/' . $purchaseRequestId . '/' . $filename,
            'original_name' => (string)$file['name'],
            'mime_type' => (string)($file['type'] ?? ''),
            'file_size' => $size,
        ];
    }

    private function unprocessable(string $message, ?string $fallback = null): void
    {
        http_response_code(422);
        $this->flash->add('danger', $message);
        header('Location: ' . ($fallback ?: ($_SERVER['HTTP_REFERER'] ?? route_to('purchase_requests'))));
        exit;
    }
}
