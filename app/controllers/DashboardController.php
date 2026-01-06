<?php
class DashboardController
{
    public function __construct(private Database $db)
    {
    }

    public function index(): void
    {
        $stats = [
            'prs' => (int)$this->db->pdo()->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn(),
            'pos' => (int)$this->db->pdo()->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn(),
            'invoices' => (int)$this->db->pdo()->query('SELECT COUNT(*) FROM invoices')->fetchColumn(),
        ];
        include __DIR__ . '/../views/dashboard/index.php';
    }
}
