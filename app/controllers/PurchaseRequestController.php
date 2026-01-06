<?php
class PurchaseRequestController
{
    public function __construct(
        private PurchaseRequestRepository $repo,
        private SupplierRepository $supplierRepo,
        private QuotationRepository $quotationRepo,
        private PurchaseOrderRepository $poRepo,
        private AttachmentRepository $attachmentRepo,
        private AuditLogger $audit,
        private Auth $auth,
        private Flash $flash,
        private SettingsRepository $settings,
        private AuthMiddleware $authMiddleware
    ) {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['solicitante', 'aprobador', 'compras', 'administrador']);
        $requests = $this->repo->all();
        include __DIR__ . '/../views/purchase_requests/index.php';
    }

    public function create(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['solicitante', 'administrador']);
        $suppliers = $this->supplierRepo->all();
        include __DIR__ . '/../views/purchase_requests/create.php';
    }

    public function store(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['solicitante', 'administrador']);
        $data = $this->validateRequest();
        $prId = $this->repo->create($this->auth->user()['id'], $data);
        $this->audit->log($this->auth->user()['id'], 'pr_create', ['pr_id' => $prId]);
        $this->handleUpload($prId);
        $this->flash->add('success', 'Solicitud creada');
        header('Location: ' . route_to('purchase_requests'));
    }

    public function edit(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['solicitante', 'administrador']);
        $id = (int)($_GET['id'] ?? 0);
        $pr = $this->repo->find($id);
        if (!$pr) {
            http_response_code(404);
            echo 'No encontrado';
            return;
        }
        include __DIR__ . '/../views/purchase_requests/edit.php';
    }

    public function update(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['solicitante', 'administrador']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if (!$pr || $pr['status'] !== 'BORRADOR') {
            $this->flash->add('danger', 'Solo se pueden editar borradores');
            header('Location: ' . route_to('purchase_requests'));
            return;
        }
        $data = $this->validateRequest();
        $this->repo->update($id, $data);
        $this->handleUpload($id);
        $this->audit->log($this->auth->user()['id'], 'pr_update', ['pr_id' => $id]);
        $this->flash->add('success', 'Solicitud actualizada');
        header('Location: ' . route_to('purchase_requests'));
    }

    public function send(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['solicitante', 'administrador']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'BORRADOR') {
            if (!$this->repo->canSend($pr)) {
                $this->flash->add('danger', 'Completa justificación, área, centro de costo e ítems antes de enviar.');
            } else {
                $this->repo->changeStatus($id, 'ENVIADA');
                $this->audit->log($this->auth->user()['id'], 'pr_send', ['pr_id' => $id]);
                $this->flash->add('success', 'Solicitud enviada');
            }
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function approve(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['aprobador', 'administrador']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'ENVIADA') {
            $this->repo->changeStatus($id, 'APROBADA');
            $this->audit->log($this->auth->user()['id'], 'pr_approve', ['pr_id' => $id]);
            $this->flash->add('success', 'Solicitud aprobada');
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function reject(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['aprobador', 'administrador']);
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'ENVIADA') {
            if ($reason === '') {
                $this->flash->add('danger', 'Se requiere motivo de rechazo');
            } else {
                $this->repo->reject($id, $reason);
                $this->audit->log($this->auth->user()['id'], 'pr_reject', ['pr_id' => $id, 'reason' => $reason]);
                $this->flash->add('danger', 'Solicitud rechazada');
            }
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function quotations(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $prId = (int)($_GET['id'] ?? 0);
        $pr = $this->repo->find($prId);
        $suppliers = $this->supplierRepo->all();
        $quotations = $this->quotationRepo->forPr($prId);
        include __DIR__ . '/../views/quotations/index.php';
    }

    public function addQuotation(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $prId = (int)($_POST['pr_id'] ?? 0);
        $data = [
            'supplier_id' => (int)$_POST['supplier_id'],
            'amount' => (float)$_POST['amount'],
            'lead_time_days' => (int)($_POST['lead_time_days'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
        $pdfPath = $this->handleQuotationUpload();
        if (!$pdfPath) {
            $this->flash->add('danger', 'Adjunta la cotización en PDF');
            header('Location: ' . route_to('quotations', ['id' => $prId]));
            return;
        }
        $this->quotationRepo->create($prId, $data, $pdfPath);
        $this->audit->log($this->auth->user()['id'], 'quotation_create', ['pr_id' => $prId]);
        $this->flash->add('success', 'Cotización registrada');
        header('Location: ' . route_to('quotations', ['id' => $prId]));
    }

    public function createPo(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $prId = (int)($_POST['pr_id'] ?? 0);
        $pr = $this->repo->find($prId);
        if (!$pr || $pr['status'] !== 'APROBADA') {
            $this->flash->add('danger', 'La PR debe estar aprobada para generar la OC');
            header('Location: ' . route_to('quotations', ['id' => $prId]));
            return;
        }

        $items = $pr['items'];
        $supplierId = (int)$_POST['supplier_id'];
        $poData = [
            'supplier_id' => $supplierId,
            'total_amount' => array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_price'], $items)),
            'items' => array_map(fn($i) => [
                'description' => $i['description'],
                'quantity' => $i['quantity'],
                'unit_price' => $i['unit_price'],
            ], $items),
        ];
        $poId = $this->poRepo->create($prId, $poData);
        $this->audit->log($this->auth->user()['id'], 'po_create', ['po_id' => $poId, 'pr_id' => $prId]);
        $this->flash->add('success', 'Orden de compra generada');
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }

    private function validateRequest(): array
    {
        $title = trim($_POST['title'] ?? '');
        $justification = trim($_POST['justification'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $cost_center = trim($_POST['cost_center'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $items = $_POST['items'] ?? [];
        $cleanItems = [];
        foreach ($items as $item) {
            if (!empty($item['description']) && $item['quantity'] > 0) {
                $cleanItems[] = [
                    'description' => trim($item['description']),
                    'quantity' => (float)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                ];
            }
        }

        if ($title === '' || $justification === '' || $area === '' || $cost_center === '' || empty($cleanItems)) {
            $this->flash->add('danger', 'Título, justificación, área, centro de costo e ítems son obligatorios');
            $fallback = $_SERVER['HTTP_REFERER'] ?? route_to('purchase_requests');
            header('Location: ' . $fallback);
            exit;
        }
        return compact('title', 'description', 'justification', 'area', 'cost_center') + ['items' => $cleanItems];
    }

    private function handleUpload(int $prId): void
    {
        if (empty($_FILES['attachment']['name'])) {
            return;
        }
        $file = $_FILES['attachment'];
        $allowed = ['application/pdf', 'image/png', 'image/jpeg'];
        if (!in_array($file['type'], $allowed, true)) {
            $this->flash->add('danger', 'Tipo de archivo no permitido');
            return;
        }
        $config = require __DIR__ . '/../config/config.php';
        $uploadDir = $config['upload_dir'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $destName = uniqid('att_') . '_' . basename($file['name']);
        $dest = $uploadDir . '/' . $destName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $publicPath = asset_url('/uploads/' . $destName);
            $this->attachmentRepo->add('purchase_request', $prId, $publicPath, $file['name'], $this->auth->user()['id']);
            $this->audit->log($this->auth->user()['id'], 'attachment_upload', ['pr_id' => $prId, 'file' => $destName]);
        }
    }

    private function handleQuotationUpload(): ?string
    {
        if (empty($_FILES['quotation_pdf']['name'])) {
            return null;
        }
        $file = $_FILES['quotation_pdf'];
        if ($file['type'] !== 'application/pdf') {
            return null;
        }
        $config = require __DIR__ . '/../config/config.php';
        $uploadDir = $config['upload_dir'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $destName = uniqid('quo_') . '_' . basename($file['name']);
        $dest = $uploadDir . '/' . $destName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return asset_url('/uploads/' . $destName);
        }
        return null;
    }
}
