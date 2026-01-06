<?php
class DashboardController
{
    public function __construct(private Database $db, private AuthMiddleware $authMiddleware)
    {
    }

    public function index(): void
    {
        $this->authMiddleware->check();

        $pdo = $this->db->pdo();
        $stats = [
            'prs' => (int)$pdo->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn(),
            'draft_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'BORRADOR'")->fetchColumn(),
            'sent_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'ENVIADA'")->fetchColumn(),
            'approved_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'APROBADA'")->fetchColumn(),
            'rejected_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'RECHAZADA'")->fetchColumn(),
            'cancelled_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'CANCELADA'")->fetchColumn(),
            'quotations' => (int)$pdo->query('SELECT COUNT(*) FROM quotations')->fetchColumn(),
            'pos' => (int)$pdo->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn(),
            'pos_open' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status IN ('CREADA','ENVIADA_A_PROVEEDOR','RECIBIDA_PARCIAL')")->fetchColumn(),
            'pos_closed' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'CERRADA'")->fetchColumn(),
            'receipts' => (int)$pdo->query('SELECT COUNT(*) FROM receipts')->fetchColumn(),
            'suppliers' => (int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn(),
            'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        ];

        include __DIR__ . '/../views/dashboard/index.php';
    }
}
