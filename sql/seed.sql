INSERT INTO users (name, email, role, password_hash, created_at) VALUES
('Administrador', 'admin@aos.com', 'administrador', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfEQ7QJfJGMrrY6Mu', NOW()),
('Solicitante Demo', 'solicitante@aos.com', 'solicitante', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfEQ7QJfJGMrrY6Mu', NOW()),
('Aprobador Demo', 'aprobador@aos.com', 'aprobador', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfJGMrrY6Mu', NOW()),
('Compras Demo', 'compras@aos.com', 'compras', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfJGMrrY6Mu', NOW()),
('Recepción Demo', 'recepcion@aos.com', 'recepcion', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfJGMrrY6Mu', NOW());

INSERT INTO suppliers (name, contact, email, phone, created_at) VALUES
('Proveedor Alfa', 'Juan Pérez', 'ventas@alfa.com', '555-1111', NOW()),
('Proveedor Beta', 'María López', 'contacto@beta.com', '555-2222', NOW());

INSERT INTO settings (`key`, value) VALUES
('company_name', 'AOS'),
('brand_logo_path', 'assets/aos-logo.svg'),
('brand_primary_color', '#0d6efd'),
('brand_accent_color', '#198754')
ON DUPLICATE KEY UPDATE value = VALUES(value);
