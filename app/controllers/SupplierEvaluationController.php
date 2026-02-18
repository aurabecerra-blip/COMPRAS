<?php
class SupplierEvaluationController
{
    public function __construct(
        private SupplierRepository $suppliers,
        private SupplierEvaluationRepository $evaluations,
        private SupplierEvaluationCalculator $calculator,
        private NotificationService $notifications,
        private SupplierEvaluationPdfBuilder $pdfBuilder,
        private Flash $flash,
        private AuditLogger $audit,
        private Auth $auth,
        private AuthMiddleware $authMiddleware
    ) {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['lider', 'administrador']);

        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');

        $suppliers = $this->suppliers->all();
        $criteria = $this->calculator->definitions();
        $evaluations = $this->evaluations->bySupplierAndDate($supplierId > 0 ? $supplierId : null, $from, $to);

        include __DIR__ . '/../views/supplier_evaluations/index.php';
    }

    public function downloadPdf(): void
    {
        $evaluationId = (int)($_GET['evaluation_id'] ?? 0);
        if ($evaluationId <= 0) {
            http_response_code(400);
            echo 'Solicitud inválida.';
            return;
        }

        $evaluation = $this->evaluations->findWithDetails($evaluationId);
        if (!$evaluation || empty($evaluation['pdf_path'])) {
            http_response_code(404);
            echo 'PDF no disponible para esta evaluación.';
            return;
        }

        $absolutePath = $this->resolvePdfAbsolutePath((string)$evaluation['pdf_path']);
        if ($absolutePath === '' || !is_file($absolutePath)) {
            http_response_code(404);
            echo 'No se encontró el archivo PDF en el servidor.';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($absolutePath) . '"');
        header('Content-Length: ' . (string)filesize($absolutePath));
        header('Cache-Control: private, max-age=300');
        readfile($absolutePath);
    }

    public function store(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['lider', 'administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . route_to('supplier_evaluations'));
            return;
        }

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $supplier = $this->suppliers->find($supplierId);
        if (!$supplier) {
            $this->flash->add('danger', 'Proveedor no encontrado.');
            header('Location: ' . route_to('supplier_evaluations'));
            return;
        }

        if (trim($supplier['email'] ?? '') === '') {
            $this->flash->add('danger', 'El proveedor no tiene correo registrado para enviar la evaluación.');
            header('Location: ' . route_to('supplier_evaluations'));
            return;
        }

        try {
            $result = $this->calculator->calculate([
                'delivery_time' => [
                    'mode' => $_POST['delivery_mode'] ?? 'on_time',
                    'breaches' => $_POST['delivery_breaches'] ?? 0,
                ],
                'quality' => $_POST['quality'] ?? '',
                'after_sales' => $_POST['after_sales'] ?? '',
                'sqr' => $_POST['sqr'] ?? '',
                'documents' => $_POST['documents'] ?? '',
            ]);
        } catch (Throwable $e) {
            $this->flash->add('danger', 'No se pudo calcular la evaluación: ' . $e->getMessage());
            header('Location: ' . route_to('supplier_evaluations'));
            return;
        }

        $user = $this->auth->user();
        $observations = trim($_POST['observations'] ?? '');

        $evaluationId = $this->evaluations->create([
            'supplier_id' => $supplierId,
            'evaluator_user_id' => (int)$user['id'],
            'total_score' => $result['total_score'],
            'status_label' => $result['status_label'],
            'observations' => $observations !== '' ? $observations : null,
        ], $result['details']);

        $evaluation = $this->evaluations->findWithDetails($evaluationId);

        $pdfGenerated = false;
        if ($evaluation) {
            try {
                $pdfPath = $this->pdfBuilder->generate($evaluation);
                $this->evaluations->attachPdf($evaluationId, $pdfPath);
                $evaluation['pdf_path'] = $pdfPath;
                $pdfGenerated = true;
            } catch (Throwable $e) {
                $this->audit->log((int)$user['id'], 'supplier_evaluation_pdf_error', [
                    'evaluation_id' => $evaluationId,
                    'supplier_id' => $supplierId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($evaluation) {
            $subject = 'Resultado de evaluación de proveedor - ' . $evaluation['status_label'];
            $body = $this->buildEmailTemplate($evaluation);
            try {
                $this->notifications->send('supplier_evaluation_completed', $subject, $body, [
                    'recipients' => [$supplier['email']],
                ]);
            } catch (Throwable $e) {
                $this->audit->log((int)$user['id'], 'supplier_evaluation_notification_error', [
                    'evaluation_id' => $evaluationId,
                    'supplier_id' => $supplierId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->audit->log((int)$user['id'], 'supplier_evaluation_create', [
            'evaluation_id' => $evaluationId,
            'supplier_id' => $supplierId,
            'total_score' => $result['total_score'],
            'status_label' => $result['status_label'],
        ]);

        $this->flash->add(
            $pdfGenerated ? 'success' : 'warning',
            $pdfGenerated
                ? 'Evaluación registrada y enviada al proveedor con su PDF.'
                : 'Evaluación registrada y enviada al proveedor, pero no se pudo generar el PDF. Verifique permisos del directorio /uploads/evaluations.'
        );
        header('Location: ' . route_to('supplier_evaluations', ['show' => $evaluationId]));
    }

    private function buildEmailTemplate(array $evaluation): string
    {
        $rows = '';
        foreach ($evaluation['details'] as $detail) {
            $rows .= '<tr>'
                . '<td style="padding:8px;border:1px solid #e6e8eb;">' . htmlspecialchars($detail['criterion_name']) . '</td>'
                . '<td style="padding:8px;border:1px solid #e6e8eb;">' . htmlspecialchars($detail['option_label']) . '</td>'
                . '<td style="padding:8px;border:1px solid #e6e8eb;text-align:right;">' . (int)$detail['score'] . '</td>'
                . '</tr>';
        }

        return '<div style="font-family:Arial,sans-serif;color:#1f2937">'
            . '<h2>Resultado de evaluación de proveedor</h2>'
            . '<p>Hola ' . htmlspecialchars($evaluation['supplier_name']) . ',</p>'
            . '<p>Se ha completado una evaluación de su desempeño como proveedor. A continuación encontrará el resumen y su archivo en PDF para descarga.</p>'
            . '<ul>'
            . '<li><strong>Proveedor:</strong> ' . htmlspecialchars($evaluation['supplier_name']) . '</li>'
            . '<li><strong>NIT:</strong> ' . htmlspecialchars($evaluation['supplier_nit'] ?: 'N/D') . '</li>'
            . '<li><strong>Servicio:</strong> ' . htmlspecialchars($evaluation['supplier_service'] ?: 'N/D') . '</li>'
            . '<li><strong>Fecha:</strong> ' . htmlspecialchars($evaluation['evaluation_date']) . '</li>'
            . '<li><strong>Puntaje total:</strong> ' . (int)$evaluation['total_score'] . ' / 100</li>'
            . '<li><strong>Resultado:</strong> ' . htmlspecialchars($evaluation['status_label']) . '</li>'
            . '<li><strong>Observaciones:</strong> ' . htmlspecialchars($evaluation['observations'] ?: 'Sin observaciones') . '</li>'
            . '</ul>'
            . (!empty($evaluation['pdf_path']) ? '<p><a href="' . htmlspecialchars(absolute_url(route_to('supplier_evaluation_pdf', ['evaluation_id' => (int)$evaluation['id']]))) . '" target="_blank">Descargar evaluación en PDF</a></p>' : '')
            . '<h3>Resumen de criterios</h3>'
            . '<table style="border-collapse:collapse;width:100%;font-size:14px">'
            . '<thead><tr>'
            . '<th style="padding:8px;border:1px solid #e6e8eb;text-align:left;background:#f9fafb">Criterio</th>'
            . '<th style="padding:8px;border:1px solid #e6e8eb;text-align:left;background:#f9fafb">Resultado</th>'
            . '<th style="padding:8px;border:1px solid #e6e8eb;text-align:right;background:#f9fafb">Puntaje</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>'
            . '</div>';
    }

    private function resolvePdfAbsolutePath(string $pdfPath): string
    {
        $trimmed = trim($pdfPath);
        if ($trimmed === '') {
            return '';
        }

        if ($trimmed[0] !== '/' && is_file($trimmed)) {
            return $trimmed;
        }

        $normalizedPath = '/' . ltrim($trimmed, '/');
        $candidates = [];

        $documentRoot = trim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot !== '') {
            $candidates[] = rtrim($documentRoot, '/\\') . $normalizedPath;
        }

        $candidates[] = __DIR__ . '/../../public' . $normalizedPath;

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}
