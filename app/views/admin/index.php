<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$activeTab = $_GET['tab'] ?? 'general';
$roles = ['administrador', 'solicitante', 'aprobador', 'compras', 'recepcion'];
?>
<h2>Administración</h2>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" href="<?= htmlspecialchars(route_to('admin', ['tab' => 'general'])) ?>">General</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'notifications' ? 'active' : '' ?>" href="<?= htmlspecialchars(route_to('admin', ['tab' => 'notifications'])) ?>">Notificaciones</a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade <?= $activeTab === 'general' ? 'show active' : '' ?>" id="tab-general">
        <div class="row">
            <div class="col-md-6">
                <h4>Configuración de marca</h4>
                <form method="post" action="<?= htmlspecialchars(route_to('admin_settings')) ?>" enctype="multipart/form-data">
                    <div class="mb-2">
                        <label class="form-label">Nombre de compañía</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('company_name', 'AOS')) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Logo (URL o ruta local)</label>
                        <input type="text" name="brand_logo_path" class="form-control mb-2" value="<?= htmlspecialchars($settingsRepo->get('brand_logo_path', 'assets/aos-logo.svg')) ?>">
                        <input type="file" name="brand_logo_file" class="form-control" accept="image/*">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Color primario</label>
                            <input type="color" name="brand_primary_color" class="form-control form-control-color" value="<?= htmlspecialchars($settingsRepo->get('brand_primary_color', '#0d6efd')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color acento</label>
                            <input type="color" name="brand_accent_color" class="form-control form-control-color" value="<?= htmlspecialchars($settingsRepo->get('brand_accent_color', '#198754')) ?>">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Áreas disponibles (separar por coma o salto de línea)</label>
                        <textarea name="form_areas" class="form-control" rows="2"><?= htmlspecialchars($settingsRepo->get('form_areas', 'Operaciones,Finanzas,TI,Calidad')) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Centros de costo disponibles</label>
                        <textarea name="form_cost_centers" class="form-control" rows="2"><?= htmlspecialchars($settingsRepo->get('form_cost_centers', 'CC-001,CC-002,CC-003')) ?></textarea>
                    </div>
                    <button class="btn btn-primary">Guardar</button>
                </form>
            </div>
            <div class="col-md-6">
                <h4>Usuarios</h4>
                <table class="table table-sm">
                    <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr><td><?= htmlspecialchars($u['name']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" action="<?= htmlspecialchars(route_to('admin_user_store')) ?>">
                    <div class="row g-2 mb-2">
                        <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Nombre"></div>
                        <div class="col-md-4"><input type="email" name="email" class="form-control" placeholder="Email"></div>
                        <div class="col-md-4"><select name="role" class="form-select">
                            <option value="administrador">Administrador</option>
                            <option value="solicitante">Solicitante</option>
                            <option value="aprobador">Aprobador</option>
                            <option value="compras">Compras</option>
                            <option value="recepcion">Recepción</option>
                        </select></div>
                    </div>
                    <div class="mb-2">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña temporal">
                    </div>
                    <button class="btn btn-secondary">Crear usuario</button>
                </form>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?= $activeTab === 'notifications' ? 'show active' : '' ?>" id="tab-notifications">
        <div class="row">
            <div class="col-lg-6">
                <h4>Estado general</h4>
                <form method="post" action="<?= htmlspecialchars(route_to('admin_notifications')) ?>">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notifications_enabled" name="notifications_enabled" value="1" <?= $settingsRepo->get('notifications_enabled', '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notifications_enabled">Activar notificaciones</label>
                    </div>
                    <h5 class="mt-3">Correo electrónico (SMTP)</h5>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="notifications_email_enabled" name="notifications_email_enabled" value="1" <?= $settingsRepo->get('notifications_email_enabled', '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notifications_email_enabled">Canal de correo activo</label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Servidor SMTP</label>
                        <input type="text" name="notifications_smtp_host" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_smtp_host', '')) ?>">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Puerto</label>
                            <input type="number" name="notifications_smtp_port" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_smtp_port', '')) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Tipo de seguridad</label>
                            <?php $security = $settingsRepo->get('notifications_smtp_security', 'tls'); ?>
                            <select name="notifications_smtp_security" class="form-select">
                                <option value="tls" <?= $security === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= $security === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= $security === 'none' ? 'selected' : '' ?>>Ninguno</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Usuario SMTP</label>
                        <input type="text" name="notifications_smtp_user" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_smtp_user', '')) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Contraseña SMTP</label>
                        <input type="password" name="notifications_smtp_password" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_smtp_password', '')) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Correo remitente</label>
                        <input type="email" name="notifications_from_email" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_from_email', '')) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nombre del remitente</label>
                        <input type="text" name="notifications_from_name" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_from_name', '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo de prueba</label>
                        <input type="email" name="notifications_test_email" class="form-control" value="<?= htmlspecialchars($settingsRepo->get('notifications_test_email', '')) ?>" placeholder="pruebas@empresa.com">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Guardar configuración</button>
                        <button class="btn btn-outline-secondary" type="submit" formaction="<?= htmlspecialchars(route_to('admin_notifications_test')) ?>">Enviar correo de prueba</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-6">
                <h4>Tipos de notificación</h4>
                <form method="post" action="<?= htmlspecialchars(route_to('admin_notification_types')) ?>">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Canal</th>
                                <th>Activo</th>
                                <th>Roles</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($notificationTypes as $type): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($type['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($type['code']) ?></small>
                                        <?php if (!empty($type['description'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($type['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="types[<?= (int)$type['id'] ?>][channel]" class="form-select form-select-sm">
                                            <option value="email" <?= $type['channel'] === 'email' ? 'selected' : '' ?>>Correo</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="form-check-input" name="types[<?= (int)$type['id'] ?>][is_active]" value="1" <?= $type['is_active'] ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($roles as $role): ?>
                                                <?php $checked = in_array($role, $type['roles'] ?? [], true); ?>
                                                <div class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input" type="checkbox" name="types[<?= (int)$type['id'] ?>][roles][]" value="<?= htmlspecialchars($role) ?>" <?= $checked ? 'checked' : '' ?>>
                                                    <label class="form-check-label small"><?= htmlspecialchars(ucfirst($role)) ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary">Guardar tipos</button>
                </form>
                <h5 class="mt-4">Agregar tipo</h5>
                <form method="post" action="<?= htmlspecialchars(route_to('admin_notification_type_create')) ?>">
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Código</label>
                            <input type="text" name="new_type_code" class="form-control" placeholder="ej: alerta_stock">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="new_type_name" class="form-control" placeholder="Alerta de stock">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Canal</label>
                            <select name="new_type_channel" class="form-select">
                                <option value="email">Correo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="new_type_description" class="form-control" placeholder="Describe cuándo se dispara">
                    </div>
                    <button class="btn btn-outline-primary">Crear tipo</button>
                </form>
            </div>
        </div>
        <div class="mt-4">
            <h4>Log de notificaciones</h4>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Canal</th>
                        <th>Destinatario</th>
                        <th>Estado</th>
                        <th>Error</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($notificationLogs)): ?>
                        <tr><td colspan="6" class="text-muted">Sin registros.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notificationLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= htmlspecialchars($log['type_name'] ?? 'N/D') ?></td>
                                <td><?= htmlspecialchars($log['channel']) ?></td>
                                <td><?= htmlspecialchars($log['recipient']) ?></td>
                                <td>
                                    <?php if ($log['status'] === 'enviado'): ?>
                                        <span class="badge text-bg-success">Enviado</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($log['error_message'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
