<?php
class SupplierController
{
    public function __construct(private SupplierRepository $repo, private Flash $flash, private AuditLogger $audit, private Auth $auth, private AuthMiddleware $authMiddleware)
    {
    }

    public function index(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $suppliers = $this->repo->all();
        include __DIR__ . '/../views/suppliers/index.php';
    }

    public function store(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'nit' => trim($_POST['nit'] ?? ''),
            'service' => trim($_POST['service'] ?? ''),
            'contact' => trim($_POST['contact'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];
        if ($data['name'] === '') {
            $this->flash->add('danger', 'El nombre es obligatorio');
            header('Location: ' . route_to('suppliers'));
            return;
        }
        $this->repo->create($data);
        $this->audit->log($this->auth->user()['id'], 'supplier_create', $data);
        $this->flash->add('success', 'Proveedor registrado');
        header('Location: ' . route_to('suppliers'));
    }
}
