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
        $this->auth->requireRole(['requester', 'approver', 'buyer', 'admin']);
        $requests = $this->repo->all();
        include __DIR__ . '/../views/purchase_requests/index.php';
    }

    public function create(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['requester', 'admin']);
        $suppliers = $this->supplierRepo->all();
        include __DIR__ . '/../views/purchase_requests/create.php';
    }

    public function store(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['requester', 'admin']);
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
        $this->auth->requireRole(['requester', 'admin']);
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
        $this->auth->requireRole(['requester', 'admin']);
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
        $this->auth->requireRole(['requester', 'admin']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'BORRADOR') {
            $this->repo->changeStatus($id, 'ENVIADA');
            $this->audit->log($this->auth->user()['id'], 'pr_send', ['pr_id' => $id]);
            $this->flash->add('success', 'Solicitud enviada');
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function approve(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['approver', 'admin']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'EN_APROBACION') {
            $this->repo->changeStatus($id, 'APROBADA');
            $this->audit->log($this->auth->user()['id'], 'pr_approve', ['pr_id' => $id]);
            $this->flash->add('success', 'Solicitud aprobada');
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function reject(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['approver', 'admin']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'EN_APROBACION') {
            $this->repo->changeStatus($id, 'RECHAZADA');
            $this->audit->log($this->auth->user()['id'], 'pr_reject', ['pr_id' => $id]);
            $this->flash->add('danger', 'Solicitud rechazada');
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function startApproval(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['approver', 'admin']);
        $id = (int)($_POST['id'] ?? 0);
        $pr = $this->repo->find($id);
        if ($pr && $pr['status'] === 'ENVIADA') {
            $this->repo->changeStatus($id, 'EN_APROBACION');
            $this->audit->log($this->auth->user()['id'], 'pr_in_review', ['pr_id' => $id]);
            $this->flash->add('info', 'Solicitud en aprobación');
        }
        header('Location: ' . route_to('purchase_requests'));
    }

    public function quotations(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['buyer', 'admin']);
        $prId = (int)($_GET['id'] ?? 0);
        $pr = $this->repo->find($prId);
        $suppliers = $this->supplierRepo->all();
        $quotations = $this->quotationRepo->forPr($prId);
        include __DIR__ . '/../views/quotations/index.php';
    }

    public function addQuotation(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['buyer', 'admin']);
        $prId = (int)($_POST['pr_id'] ?? 0);
        $data = [
            'supplier_id' => (int)$_POST['supplier_id'],
            'amount' => (float)$_POST['amount'],
            'notes' => trim($_POST['notes'] ?? ''),
        ];
        $this->quotationRepo->create($prId, $data);
        $this->audit->log($this->auth->user()['id'], 'quotation_create', ['pr_id' => $prId]);
        $this->flash->add('success', 'Cotización registrada');
        header('Location: ' . route_to('quotations', ['id' => $prId]));
    }

    public function createPo(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['buyer', 'admin']);
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

        if ($title === '' || empty($cleanItems)) {
            $this->flash->add('danger', 'Título e ítems son obligatorios');
            $fallback = $_SERVER['HTTP_REFERER'] ?? route_to('purchase_requests');
            header('Location: ' . $fallback);
            exit;
        }
        return compact('title', 'description') + ['items' => $cleanItems];
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
            $publicPath = asset_url('/public/uploads/' . $destName);
            $this->attachmentRepo->add($prId, $publicPath, $file['name'], $this->auth->user()['id']);
            $this->audit->log($this->auth->user()['id'], 'attachment_upload', ['pr_id' => $prId, 'file' => $destName]);
        }
    }
}
