<?php
class ProviderSelectionController
{
    public function __construct(
        private PurchaseRequestRepository $purchaseRequests,
        private SupplierRepository $suppliers,
        private ProviderQuoteRepository $quotes,
        private ProviderSelectionRepository $selections,
        private ProviderSelectionScoringService $scoring,
        private PdfGeneratorService $pdf,
        private AuditLogger $audit,
        private Auth $auth,
        private Flash $flash,
        private AuthMiddleware $authMiddleware
    ) {
    }

    public function evaluate(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $evaluation = $this->selections->getOrCreateEvaluation($purchaseRequestId);

        $latestQuotes = $this->quotes->latestQuotesByProvider($purchaseRequestId);
        $scores = $this->scoring->buildScoresFromQuotes($latestQuotes);

        foreach ($scores as $score) {
            $this->selections->upsertScore(
                (int)$evaluation['id'],
                (int)$score['provider_id'],
                $score,
                $score['detail'],
                trim((string)($score['observations'] ?? '')) ?: null
            );
        }

        $this->selections->updateDraftObservations((int)$evaluation['id'], trim((string)($_POST['observations'] ?? '')) ?: null);
        $this->audit->log((int)$this->auth->user()['id'], 'provider_selection_draft_save', ['purchase_request_id' => $purchaseRequestId]);

        $this->flash->add('success', 'Evaluación guardada en borrador.');
        header('Location: ' . route_to('provider_selection', ['id' => $purchaseRequestId]));
    }

    public function close(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider']);

        $purchaseRequestId = (int)($_POST['purchase_request_id'] ?? 0);
        $distinctProviders = $this->quotes->countDistinctProvidersForMinimum($purchaseRequestId);
        if ($distinctProviders < 3) {
            http_response_code(422);
            $this->flash->add('danger', 'No se puede cerrar la evaluación: se requieren mínimo 3 cotizaciones de proveedores distintos.');
            header('Location: ' . route_to('provider_selection', ['id' => $purchaseRequestId]));
            return;
        }

        $evaluation = $this->selections->getOrCreateEvaluation($purchaseRequestId);
        $latestQuotes = $this->quotes->latestQuotesByProvider($purchaseRequestId);
        $autoScores = $this->scoring->buildScoresFromQuotes($latestQuotes);

        foreach ($autoScores as $score) {
            $this->selections->upsertScore(
                (int)$evaluation['id'],
                (int)$score['provider_id'],
                $score,
                $score['detail'],
                null
            );
        }

        $scores = $this->selections->scores((int)$evaluation['id']);
        if (count($scores) < 3) {
            http_response_code(422);
            $this->flash->add('danger', 'Debe registrar cotizaciones para al menos 3 proveedores.');
            header('Location: ' . route_to('provider_selection', ['id' => $purchaseRequestId]));
            return;
        }

        try {
            $winner = $this->scoring->resolveWinner(
                $scores,
                (int)($_POST['manual_winner_provider_id'] ?? 0),
                trim((string)($_POST['tie_break_reason'] ?? ''))
            );
        } catch (Throwable $e) {
            http_response_code(422);
            $this->flash->add('danger', $e->getMessage());
            header('Location: ' . route_to('provider_selection', ['id' => $purchaseRequestId]));
            return;
        }

        $pr = $this->purchaseRequests->find($purchaseRequestId);
        $allProviders = $this->suppliers->all();
        $providersById = [];
        foreach ($allProviders as $provider) {
            $providersById[(int)$provider['id']] = $provider;
        }

        $criteria = [
            'experiencia' => ['label' => 'Experiencia', 'ponderacion' => 15, 'descripcion' => '<2 años=5 / 2-5 años=10 / >5 años=15'],
            'forma_pago' => ['label' => 'Forma de Pago', 'ponderacion' => 25, 'descripcion' => 'Contado=10 / Credicontado=20 / Crédito 30+=25 / N/A técnico'],
            'entrega' => ['label' => 'Condiciones de Entrega', 'ponderacion' => 20, 'descripcion' => '>10=5 / 10=10 / <5=20 / N/A técnico'],
            'descuento' => ['label' => 'Descuento / Valor Agregado', 'ponderacion' => 5, 'descripcion' => 'Sí=5 / No=0'],
            'certificaciones' => ['label' => 'Certificaciones', 'ponderacion' => 10, 'descripcion' => '1 cert=5 / 2+ cert=10'],
            'precios' => ['label' => 'Precios', 'ponderacion' => 25, 'descripcion' => 'Mayor=5 / Igual=15 / Menor=25'],
        ];

        $pdfPath = $this->pdf->generateProviderSelectionPdf([
            'purchase_request' => $pr ?? ['id' => $purchaseRequestId, 'title' => 'N/D'],
            'scores' => $scores,
            'criteria' => $criteria,
            'winner_name' => $providersById[$winner['winner_provider_id']]['name'] ?? 'N/D',
            'observations' => trim((string)($_POST['observations'] ?? '')),
        ]);

        $this->selections->closeEvaluation((int)$evaluation['id'], [
            'closed_by' => (int)$this->auth->user()['id'],
            'winner_provider_id' => $winner['winner_provider_id'],
            'tie_break_reason' => $winner['tie_break_reason'],
            'observations' => trim((string)($_POST['observations'] ?? '')) ?: null,
            'pdf_path' => $pdfPath,
        ]);

        $this->purchaseRequests->selectSupplier($purchaseRequestId, $winner['winner_provider_id'], $winner['tie_break_reason'] ?? 'Proveedor seleccionado por mayor puntaje en evaluación.');

        $this->audit->log((int)$this->auth->user()['id'], 'provider_selection_close', [
            'purchase_request_id' => $purchaseRequestId,
            'winner_provider_id' => $winner['winner_provider_id'],
            'pdf_path' => $pdfPath,
        ]);

        $this->flash->add('success', 'Evaluación cerrada y proveedor ganador seleccionado.');
        header('Location: ' . route_to('provider_selection', ['id' => $purchaseRequestId]));
    }

    public function pdf(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador', 'lider', 'aprobador']);

        $evaluationId = (int)($_GET['evaluation_id'] ?? 0);
        $evaluation = $this->selections->find($evaluationId);
        if (!$evaluation || empty($evaluation['pdf_path'])) {
            http_response_code(404);
            echo 'PDF no encontrado';
            return;
        }

        $absolutePath = __DIR__ . '/../../public' . $evaluation['pdf_path'];
        if (!is_file($absolutePath)) {
            http_response_code(404);
            echo 'Archivo no disponible';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="analisis_seleccion_' . $evaluationId . '.pdf"');
        readfile($absolutePath);
    }
}
