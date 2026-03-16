<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <p class="text-uppercase text-muted small mb-1">ISO · Evaluación de proveedores</p>
        <h3 class="mb-0">Proveedores</h3>
        <p class="text-muted mb-0">Seguimiento de desempeño, entregas y cumplimiento.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-success" href="<?= htmlspecialchars(route_to('suppliers_export_template')) ?>">
            <i class="bi bi-file-earmark-excel"></i> Plantilla Excel (CSV)
        </a>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars(route_to('suppliers_export')) ?>">
            <i class="bi bi-download"></i> Exportar proveedores
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h5 class="mb-3">Importación masiva</h5>
        <form method="post" action="<?= htmlspecialchars(route_to('suppliers_import')) ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
            <div class="col-md-8 col-lg-6">
                <label class="form-label">Archivo CSV</label>
                <input type="file" name="suppliers_file" class="form-control" accept=".csv,text/csv" required>
                <small class="text-muted">Descarga la plantilla, completa los datos en Excel y guárdala como CSV UTF-8.</small>
            </div>
            <div class="col-md-4 col-lg-3">
                <button class="btn btn-success w-100"><i class="bi bi-upload"></i> Importar masivo</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-6 col-lg-4">
                <label for="supplierSearch" class="form-label">Buscar proveedor</label>
                <input type="search" id="supplierSearch" class="form-control" placeholder="Nombre, NIT, servicio o contacto">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle" id="suppliersTable">
                <thead class="table-light">
                    <tr><th>Nombre</th><th>NIT</th><th>Servicio</th><th>Líder</th><th>Estado evaluación</th><th>Documentos completos</th><th>Contacto</th><th>Email</th><th>Teléfono</th><th class="text-center">Cotizaciones</th><th class="text-center">OC</th><th class="text-center">OC Abiertas</th><th class="text-end">Monto OC</th><th class="text-end">Lead time prom.</th><th class="text-end">Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <?php
                        $filterValue = trim(($s['name'] ?? '') . ' ' . ($s['nit'] ?? '') . ' ' . ($s['service'] ?? '') . ' ' . ($s['contact'] ?? ''));
                        $filterValue = function_exists('mb_strtolower') ? mb_strtolower($filterValue, 'UTF-8') : strtolower($filterValue);
                        ?>
                        <tr data-filter-value="<?= htmlspecialchars($filterValue) ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['nit'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['service'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['leader_name'] ?? 'Sin asignar') ?></td>
                            <td>
                                <?php if ((int)($s['has_evaluation'] ?? 0) === 1): ?>
                                    <span class="badge bg-success-subtle text-success">Evaluado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Pendiente de evaluación</span>
                                <?php endif; ?>
                            </td>
                            <td><?= ((int)($s['documents_complete'] ?? 0) === 1) ? 'Sí' : 'No' ?></td>
                            <td><?= htmlspecialchars($s['contact']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars($s['phone']) ?></td>
                            <td class="text-center"><span class="badge bg-info-subtle text-info"><?= (int)($s['quotations_count'] ?? 0) ?></span></td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary"><?= (int)($s['pos_count'] ?? 0) ?></span></td>
                            <td class="text-center"><span class="badge bg-warning-subtle text-warning"><?= (int)($s['open_pos'] ?? 0) ?></span></td>
                            <td class="text-end"><?= number_format((float)($s['pos_spend'] ?? 0), 2) ?></td>
                            <td class="text-end"><?= $s['avg_lead_time'] ? number_format((float)$s['avg_lead_time'], 0) . ' días' : 'N/D' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= (int)$s['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="<?= htmlspecialchars(route_to('supplier_delete')) ?>" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este proveedor?');">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-muted small mb-0 d-none" id="suppliersNoResults">No se encontraron proveedores con ese criterio.</p>
        </div>
    </div>
</div>

<div class="row g-3 align-items-stretch">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Nuevo proveedor</h5>
                <form method="post" action="<?= htmlspecialchars(route_to('supplier_store')) ?>">
                    <div class="row g-2 mb-2">
                        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nombre" required></div>
                        <div class="col-md-3"><input type="text" name="nit" class="form-control" placeholder="NIT"></div>
                        <div class="col-md-3"><input type="text" name="service" class="form-control" placeholder="Servicio"></div>
                        <div class="col-md-3"><input type="text" name="contact" class="form-control" placeholder="Contacto"></div>
                        <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email"></div>
                        <div class="col-md-3"><input type="text" name="phone" class="form-control" placeholder="Teléfono"></div>
                        <div class="col-md-6">
                            <select name="leader_user_id" class="form-select">
                                <option value="0">Líder asignado (opcional)</option>
                                <?php foreach (($leaders ?? []) as $leader): ?>
                                    <option value="<?= (int)$leader['id'] ?>"><?= htmlspecialchars($leader['name']) ?> (<?= htmlspecialchars($leader['role']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Checklist ISO</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Evidencia de certificados vigentes.</li>
                    <li class="mb-2"><i class="bi bi-truck text-primary"></i> Lead time promedio controlado.</li>
                    <li class="mb-2"><i class="bi bi-cash-stack text-warning"></i> OC abiertas bajo seguimiento.</li>
                    <li class="mb-0"><i class="bi bi-bar-chart-line text-info"></i> Registro histórico de cotizaciones.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php foreach ($suppliers as $s): ?>
    <div class="modal fade" id="editSupplierModal<?= (int)$s['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= htmlspecialchars(route_to('supplier_update')) ?>">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($s['name']) ?>" required></div>
                            <div class="col-md-6"><label class="form-label">NIT</label><input type="text" name="nit" class="form-control" value="<?= htmlspecialchars($s['nit'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label">Servicio</label><input type="text" name="service" class="form-control" value="<?= htmlspecialchars($s['service'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label">Contacto</label><input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($s['contact'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($s['email'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($s['phone'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label">Líder asignado</label>
                                <select name="leader_user_id" class="form-select">
                                    <option value="0">Sin asignar</option>
                                    <?php foreach (($leaders ?? []) as $leader): ?>
                                        <option value="<?= (int)$leader['id'] ?>" <?= ((int)($s['leader_user_id'] ?? 0) === (int)$leader['id']) ? 'selected' : '' ?>><?= htmlspecialchars($leader['name']) ?> (<?= htmlspecialchars($leader['role']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('supplierSearch');
    const table = document.getElementById('suppliersTable');
    const noResults = document.getElementById('suppliersNoResults');

    if (!searchInput || !table) {
        return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr'));

    const filterRows = () => {
        const term = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {
            const target = row.getAttribute('data-filter-value') || '';
            const isVisible = term === '' || target.includes(term);
            row.classList.toggle('d-none', !isVisible);
            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (noResults) {
            noResults.classList.toggle('d-none', visibleCount > 0);
        }
    };

    searchInput.addEventListener('input', filterRows);
});
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
