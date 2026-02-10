INSERT INTO suppliers (name, contact, email, phone, created_at) VALUES
('Proveedor Alfa', 'Juan Pérez', 'ventas@alfa.com', '555-1111', NOW()),
('Proveedor Beta', 'María López', 'contacto@beta.com', '555-2222', NOW());

INSERT INTO settings (`key`, value) VALUES
('company_name', 'AOS'),
('brand_logo_path', 'assets/aos-logo.svg'),
('brand_primary_color', '#0d6efd'),
('brand_accent_color', '#198754'),
('notifications_enabled', '0'),
('notifications_email_enabled', '0'),
('notifications_smtp_host', ''),
('notifications_smtp_port', ''),
('notifications_smtp_security', 'tls'),
('notifications_smtp_user', ''),
('notifications_smtp_password', ''),
('notifications_from_email', ''),
('notifications_from_name', ''),
('notifications_test_email', '')
ON DUPLICATE KEY UPDATE value = VALUES(value);

INSERT INTO notification_types (code, name, description, channel, is_active) VALUES
('purchase_request_created', 'Solicitud creada', 'Creación de una solicitud de compra', 'email', 1),
('purchase_request_sent', 'Solicitud enviada', 'Envío de solicitud a aprobación', 'email', 1),
('purchase_request_approved', 'Solicitud aprobada', 'Aprobación de una solicitud', 'email', 1),
('purchase_request_rejected', 'Solicitud rechazada', 'Rechazo de una solicitud', 'email', 1),
('test_email', 'Correo de prueba', 'Mensaje de prueba para SMTP', 'email', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), channel = VALUES(channel), is_active = VALUES(is_active);

INSERT INTO notification_type_roles (notification_type_id, role)
SELECT id, 'aprobador' FROM notification_types WHERE code = 'purchase_request_sent'
ON DUPLICATE KEY UPDATE role = VALUES(role);
INSERT INTO notification_type_roles (notification_type_id, role)
SELECT id, 'compras' FROM notification_types WHERE code IN ('purchase_request_approved', 'purchase_request_rejected')
ON DUPLICATE KEY UPDATE role = VALUES(role);
