<?php
class AuditController
{
    public function __construct(private Database $db, private Auth $auth, private AuthMiddleware $authMiddleware)
    {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['admin', 'accountant']);
        $logs = $this->db->pdo()->query('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 200')->fetchAll();
        include __DIR__ . '/../views/admin/audit.php';
    }
}
