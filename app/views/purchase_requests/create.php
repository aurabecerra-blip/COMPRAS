<?php
$areaOptions = setting_list($settingsRepo, 'form_areas', ['Operaciones', 'Finanzas', 'TI', 'Calidad']);
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <p class="text-uppercase text-muted small mb-1">Flujo ISO</p>
        <h3 class="mb-0">Nueva Solicitud de Compra</h3>
        <p class="text-muted mb-0">Captura los datos mínimos para enviar la solicitud a aprobación.</p>
    </div>
    <span class="badge bg-primary-subtle text-primary fw-semibold">Solicitud → Aprobación → OC</span>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form method="post" action="<?= htmlspecialchars(route_to('purchase_request_store')) ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control form-control-lg" placeholder="Ej: Licencias de software para equipo de diseño" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Justificación</label>
                        <textarea name="justification" class="form-control" rows="3" placeholder="Explica el por qué de la necesidad" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Área</label>
                        <input list="areaOptions" type="text" name="area" class="form-control" placeholder="Selecciona o escribe" required>
                        <datalist id="areaOptions">
                            <?php foreach ($areaOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Detalles adicionales, consideraciones de instalación o servicio"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Ítems</label>
                            <small class="text-muted">Mínimo un ítem con descripción y cantidad</small>
                        </div>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6"><input type="text" name="items[<?= $i ?>][description]" class="form-control" placeholder="Descripción"></div>
                                <div class="col-md-3"><input type="number" step="0.01" name="items[<?= $i ?>][quantity]" class="form-control" placeholder="Cantidad"></div>
                                
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjunto (PDF/imagen)</label>
                        <input type="file" name="attachment" class="form-control" accept="application/pdf,image/*">
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary px-4"><i class="bi bi-send"></i> Guardar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Ciclo controlado</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-shield-check text-success"></i> Validación de campos obligatorios.</li>
                    <li class="mb-2"><i class="bi bi-qr-code text-primary"></i> Código de seguimiento automático.</li>
                    <li class="mb-2"><i class="bi bi-envelope-check text-info"></i> Notificación al correo corporativo.</li>
                    <li class="mb-0"><i class="bi bi-diagram-3 text-warning"></i> Flujo alineado a ISO 9001.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
