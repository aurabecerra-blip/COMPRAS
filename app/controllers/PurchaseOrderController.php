<?php
class PurchaseOrderController
{
    public function __construct(
        private Database $db,
        private PurchaseOrderRepository $repo,
        private PurchaseRequestRepository $prRepo,
        private ReceptionRepository $receptionRepo,
        private AuditLogger $audit,
        private Auth $auth,
        private Flash $flash,
        private AuthMiddleware $authMiddleware
    ) {
    }

    public function show(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'recepcion', 'administrador']);
        $id = (int)($_GET['id'] ?? 0);
        $po = $this->fetchPo($id);
        if (!$po) {
            http_response_code(404);
            echo 'OC no encontrada';
            return;
        }
        $receipts = $this->repo->receipts($id);
        include __DIR__ . '/../views/purchase_orders/show.php';
    }

    private function fetchPo(int $id): ?array
    {
        $query = $this->db->pdo()->prepare('SELECT po.*, s.name as supplier_name, pr.title as pr_title FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id LEFT JOIN purchase_requests pr ON pr.id = po.purchase_request_id WHERE po.id = ?');
        $query->execute([$id]);
        $po = $query->fetch();
        if (!$po) {
            return null;
        }
        $itemsStmt = $this->db->pdo()->prepare('SELECT * FROM po_items WHERE purchase_order_id = ?');
        $itemsStmt->execute([$id]);
        $po['items'] = $itemsStmt->fetchAll();
        return $po;
    }

    public function receive(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['recepcion', 'compras', 'administrador']);
        $poId = (int)($_POST['po_id'] ?? 0);
        $items = $_POST['items'] ?? [];
        $evidencePath = $this->handleEvidenceUpload();
        $filtered = array_filter($items, fn($qty) => $qty > 0);
        if (empty($filtered)) {
            $this->flash->add('danger', 'Ingresa cantidades para registrar la recepción.');
            header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
            return;
        }
        $this->receptionRepo->create($poId, $filtered, $this->auth->user()['id'], $evidencePath, trim($_POST['notes'] ?? ''));
        $this->repo->refreshStatusFromReceipts($poId);
        $this->audit->log($this->auth->user()['id'], 'po_receive', ['po_id' => $poId, 'items' => $filtered]);
        $this->flash->add('success', 'Recepción registrada');
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }

    public function close(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $poId = (int)($_POST['po_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $po = $this->fetchPo($poId);
        if (!$po) {
            http_response_code(404);
            echo 'OC no encontrada';
            return;
        }
        $status = $po['status'];
        $canClose = $status === 'RECIBIDA TOTAL' || ($status === 'RECIBIDA PARCIAL' && $reason !== '');
        if ($canClose) {
            $this->repo->close($poId, $reason ?: null);
            $this->audit->log($this->auth->user()['id'], 'po_close', ['po_id' => $poId, 'reason' => $reason]);
            $this->flash->add('success', 'OC cerrada');
        } else {
            $this->flash->add('danger', 'Se requiere recepción total o justificación para cerrar');
        }
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }

    public function sendToSupplier(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $poId = (int)($_POST['po_id'] ?? 0);
        $po = $this->fetchPo($poId);
        if ($po && $po['status'] === 'CREADA') {
            $this->repo->markSent($poId);
            $this->audit->log($this->auth->user()['id'], 'po_send_supplier', ['po_id' => $poId]);
            $this->flash->add('info', 'OC marcada como enviada a proveedor');
        }
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }

    private function handleEvidenceUpload(): ?string
    {
        if (empty($_FILES['evidence']['name'])) {
            return null;
        }
        $file = $_FILES['evidence'];
        $allowed = ['application/pdf', 'image/png', 'image/jpeg'];
        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }
        $config = require __DIR__ . '/../config/config.php';
        $uploadDir = $config['upload_dir'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $destName = uniqid('rec_') . '_' . basename($file['name']);
        $dest = $uploadDir . '/' . $destName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return asset_url('/uploads/' . $destName);
        }
        return null;
    }
}
