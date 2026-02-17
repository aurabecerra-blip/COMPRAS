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

    public function update(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'nit' => trim($_POST['nit'] ?? ''),
            'service' => trim($_POST['service'] ?? ''),
            'contact' => trim($_POST['contact'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];

        if ($id <= 0 || $data['name'] === '') {
            $this->flash->add('danger', 'Datos inválidos para editar proveedor.');
            header('Location: ' . route_to('suppliers'));
            return;
        }

        $this->repo->update($id, $data);
        $this->audit->log($this->auth->user()['id'], 'supplier_update', ['id' => $id] + $data);
        $this->flash->add('success', 'Proveedor actualizado correctamente.');
        header('Location: ' . route_to('suppliers'));
    }

    public function delete(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('danger', 'Proveedor inválido para eliminar.');
            header('Location: ' . route_to('suppliers'));
            return;
        }

        try {
            $this->repo->delete($id);
            $this->audit->log($this->auth->user()['id'], 'supplier_delete', ['id' => $id]);
            $this->flash->add('success', 'Proveedor eliminado.');
        } catch (Throwable $e) {
            $this->flash->add('danger', 'No se pudo eliminar el proveedor porque tiene registros asociados.');
        }

        header('Location: ' . route_to('suppliers'));
    }

    public function exportTemplate(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plantilla_proveedores.csv"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['name', 'nit', 'service', 'contact', 'email', 'phone']);
        fputcsv($out, ['Proveedor Ejemplo S.A.S.', '901234567-8', 'Suministro de papelería', 'Ana Pérez', 'ana@proveedor.com', '3001234567']);
        fclose($out);
        exit;
    }

    public function exportAll(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="proveedores.csv"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['id', 'name', 'nit', 'service', 'contact', 'email', 'phone']);
        foreach ($this->repo->all() as $supplier) {
            fputcsv($out, [
                $supplier['id'] ?? '',
                $supplier['name'] ?? '',
                $supplier['nit'] ?? '',
                $supplier['service'] ?? '',
                $supplier['contact'] ?? '',
                $supplier['email'] ?? '',
                $supplier['phone'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function importBulk(): void
    {
        $this->authMiddleware->check();
        $this->auth->requireRole(['compras', 'administrador']);

        if (!isset($_FILES['suppliers_file']) || (int)$_FILES['suppliers_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash->add('danger', 'Debes seleccionar un archivo CSV válido para importar.');
            header('Location: ' . route_to('suppliers'));
            return;
        }

        $path = $_FILES['suppliers_file']['tmp_name'];
        $handle = fopen($path, 'rb');
        if (!$handle) {
            $this->flash->add('danger', 'No fue posible leer el archivo cargado.');
            header('Location: ' . route_to('suppliers'));
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->flash->add('danger', 'El archivo está vacío.');
            header('Location: ' . route_to('suppliers'));
            return;
        }

        $normalizedHeader = array_map(static fn ($value) => strtolower(trim((string)$value)), $header);
        $expected = ['name', 'nit', 'service', 'contact', 'email', 'phone'];
        $isTemplateHeader = array_slice($normalizedHeader, 0, count($expected)) === $expected;

        if (!$isTemplateHeader) {
            rewind($handle);
        }

        $created = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, static fn ($value) => trim((string)$value) !== '')) === 0) {
                continue;
            }

            $name = trim((string)($row[0] ?? ''));
            if ($name === '') {
                continue;
            }

            $data = [
                'name' => $name,
                'nit' => trim((string)($row[1] ?? '')),
                'service' => trim((string)($row[2] ?? '')),
                'contact' => trim((string)($row[3] ?? '')),
                'email' => trim((string)($row[4] ?? '')),
                'phone' => trim((string)($row[5] ?? '')),
            ];

            $existing = $this->repo->findByName($data['name']);
            if ($existing) {
                $this->repo->update((int)$existing['id'], $data);
                $updated++;
            } else {
                $this->repo->create($data);
                $created++;
            }
        }

        fclose($handle);

        $this->audit->log($this->auth->user()['id'], 'supplier_bulk_import', ['created' => $created, 'updated' => $updated]);
        $this->flash->add('success', "Importación finalizada. Creados: {$created}, actualizados: {$updated}.");
        header('Location: ' . route_to('suppliers'));
    }
}
