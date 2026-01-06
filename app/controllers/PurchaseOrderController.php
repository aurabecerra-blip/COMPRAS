<?php
class PurchaseOrderController
{
    public function __construct(
        private Database $db,
        private PurchaseOrderRepository $repo,
        private PurchaseRequestRepository $prRepo,
        private ReceptionRepository $receptionRepo,
        private InvoiceRepository $invoiceRepo,
        private AuditLogger $audit,
        private Auth $auth,
        private Flash $flash,
        private AuthMiddleware $authMiddleware
    ) {
    }

    public function show(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['buyer', 'receiver', 'accountant', 'admin']);
        $id = (int)($_GET['id'] ?? 0);
        $po = $this->fetchPo($id);
        if (!$po) {
            http_response_code(404);
            echo 'OC no encontrada';
            return;
        }
        $receipts = $this->repo->receipts($id);
        $invoices = $this->invoiceRepo->forPo($id);
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
        $this->auth->requireRole(['receiver', 'buyer', 'admin']);
        $poId = (int)($_POST['po_id'] ?? 0);
        $items = array_filter($_POST['items'] ?? [], fn($i) => !empty($i['description']) && $i['quantity'] > 0);
        $this->receptionRepo->create($poId, $items, $this->auth->user()['id']);
        $this->audit->log($this->auth->user()['id'], 'po_receive', ['po_id' => $poId]);
        $this->flash->add('success', 'Recepción registrada');
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }

    public function addInvoice(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['accountant', 'admin']);
        $poId = (int)($_POST['po_id'] ?? 0);
        $data = [
            'invoice_number' => trim($_POST['invoice_number'] ?? ''),
            'amount' => (float)$_POST['amount'],
        ];
        $this->invoiceRepo->create($poId, $data, $this->auth->user()['id']);
        $this->audit->log($this->auth->user()['id'], 'invoice_register', ['po_id' => $poId]);
        $this->flash->add('success', 'Factura registrada');
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }

    public function close(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['buyer', 'admin']);
        $poId = (int)($_POST['po_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $receipts = $this->repo->receipts($poId);
        $po = $this->fetchPo($poId);
        $receivedQty = array_sum(array_map(fn($r) => (float)$r['received_qty'], $receipts));
        $orderedQty = array_sum(array_map(fn($i) => (float)$i['quantity'], $po['items'] ?? []));
        if ($receivedQty >= $orderedQty || $reason !== '') {
            $this->repo->close($poId, $reason ?: null);
            $this->audit->log($this->auth->user()['id'], 'po_close', ['po_id' => $poId, 'reason' => $reason]);
            $this->flash->add('success', 'OC cerrada');
        } else {
            $this->flash->add('danger', 'Se requiere recepción total o justificación para cerrar');
        }
        header('Location: ' . route_to('purchase_orders', ['id' => $poId]));
    }
}
