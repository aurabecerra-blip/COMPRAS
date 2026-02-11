<?php
class ReevaluationController
{
    public function __construct(
        private SupplierRepository $suppliers,
        private ReevaluationRepository $reevaluations,
        private ReevaluationService $service,
        private ModuleMailer $mailer,
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
        $suppliers = $this->suppliers->all();
        $criteria = $this->service->criteria();
        $reevaluations = $this->reevaluations->listAll($supplierId > 0 ? $supplierId : null);

        include __DIR__ . '/../views/reevaluations/index.php';
    }

    public function store(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['lider', 'administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . route_to('reevaluations'));
            return;
        }

        $providerId = (int)($_POST['provider_id'] ?? 0);
        $provider = $this->suppliers->find($providerId);
        if (!$provider) {
            $this->flash->add('danger', 'Proveedor no encontrado.');
            header('Location: ' . route_to('reevaluations'));
            return;
        }

        $calc = $this->service->calculate([
            'delivery_mode' => $_POST['delivery_mode'] ?? 'on_time',
            'delivery_breaches' => $_POST['delivery_breaches'] ?? 0,
            'quality' => $_POST['quality'] ?? '',
            'after_sales' => $_POST['after_sales'] ?? '',
        ]);

        $user = $this->auth->user();
        $id = $this->reevaluations->create([
            'provider_id' => $providerId,
            'provider_name' => $provider['name'],
            'provider_nit' => $provider['nit'] ?? null,
            'service_provided' => trim((string)($_POST['service_provided'] ?? ($provider['service'] ?? ''))) ?: null,
            'evaluation_date' => trim((string)($_POST['evaluation_date'] ?? '')) ?: date('Y-m-d'),
            'evaluator_user_id' => (int)$user['id'],
            'observations' => trim((string)($_POST['observations'] ?? '')) ?: null,
            'total_score' => $calc['total_score'],
        ], $calc['items']);

        $reevaluation = $this->reevaluations->findWithItems($id);
        if (!$reevaluation) {
            $this->flash->add('danger', 'No fue posible recuperar la reevaluación guardada.');
            header('Location: ' . route_to('reevaluations'));
            return;
        }

        $pdfPath = $this->service->buildPdf($reevaluation);
        $this->reevaluations->updatePdf($id, $pdfPath);
        $reevaluation['pdf_path'] = $pdfPath;

        $email = trim((string)($provider['email'] ?? ''));
        if ($email !== '') {
            $absolutePdf = __DIR__ . '/../../public' . $pdfPath;
            try {
                $ok = $this->mailer->sendWithAttachment($email, 'Reevaluación de proveedor', $this->emailBody($reevaluation), $absolutePdf, basename($absolutePdf));
                if ($ok) {
                    $this->reevaluations->updateEmailStatus($id, 'sent', null);
                } else {
                    $this->reevaluations->updateEmailStatus($id, 'failed', 'mail() devolvió false');
                    $this->flash->add('warning', 'La reevaluación se guardó, pero no se pudo enviar correo al proveedor.');
                }
            } catch (Throwable $e) {
                $this->reevaluations->updateEmailStatus($id, 'failed', $e->getMessage());
                $this->flash->add('warning', 'La reevaluación se guardó correctamente, pero falló el envío por correo.');
            }
        }

        $this->audit->log((int)$user['id'], 'provider_reevaluation_create', [
            'reevaluation_id' => $id,
            'provider_id' => $providerId,
            'total_score' => $calc['total_score'],
        ]);

        $this->flash->add('success', 'Reevaluación registrada correctamente.');
        header('Location: ' . route_to('reevaluations'));
    }

    private function emailBody(array $reevaluation): string
    {
        $rows = '';
        foreach ($reevaluation['items'] as $item) {
            $extra = $item['extra_value'] !== null ? ' (' . (int)$item['extra_value'] . ' incumplimientos)' : '';
            $rows .= '<tr><td>' . htmlspecialchars($item['criterion_name']) . '</td><td>'
                . htmlspecialchars($item['selected_label'] . $extra) . '</td><td>'
                . (int)$item['item_score'] . '</td></tr>';
        }

        return '<h2>Resultado de Reevaluación</h2>'
            . '<p><strong>Proveedor:</strong> ' . htmlspecialchars($reevaluation['provider_name']) . '</p>'
            . '<p><strong>NIT:</strong> ' . htmlspecialchars($reevaluation['provider_nit'] ?: 'N/D') . '</p>'
            . '<p><strong>Fecha:</strong> ' . htmlspecialchars($reevaluation['evaluation_date']) . '</p>'
            . '<table border="1" cellpadding="6" cellspacing="0"><thead><tr><th>Criterio</th><th>Selección</th><th>Puntaje</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p><strong>Total:</strong> ' . (int)$reevaluation['total_score'] . ' / 100</p>';
    }
}
