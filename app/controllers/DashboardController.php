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
            'approval_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status IN ('ENVIADA','EN_APROBACION')")->fetchColumn(),
            'approved_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'APROBADA'")->fetchColumn(),
            'rejected_prs' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'RECHAZADA'")->fetchColumn(),
            'quotations' => (int)$pdo->query('SELECT COUNT(*) FROM quotations')->fetchColumn(),
            'pos' => (int)$pdo->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn(),
            'pos_open' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'ABIERTA'")->fetchColumn(),
            'pos_closed' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'CERRADA'")->fetchColumn(),
            'receipts' => (int)$pdo->query('SELECT COUNT(*) FROM receipts')->fetchColumn(),
            'invoices' => (int)$pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn(),
            'suppliers' => (int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn(),
            'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        ];

        include __DIR__ . '/../views/dashboard/index.php';
    }
}
