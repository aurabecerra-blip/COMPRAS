INSERT INTO suppliers (name, nit, service, contact, email, phone, created_at) VALUES
('Proveedor Demo 1', '900100001-1', 'BIENES', 'Ana Demo', 'proveedor1@aossas.com', '3000000001', NOW()),
('Proveedor Demo 2', '900100002-2', 'SERVICIOS', 'Luis Demo', 'proveedor2@aossas.com', '3000000002', NOW()),
('Proveedor Demo 3', '900100003-3', 'SERVICIOS_TECNICOS', 'Marta Demo', 'proveedor3@aossas.com', '3000000003', NOW());

INSERT INTO purchase_requests (requester_id, tracking_code, title, justification, area, description, status, created_at, updated_at)
SELECT u.id, CONCAT('PR-DEMO-', UPPER(SUBSTRING(MD5(RAND()), 1, 5))), 'Solicitud Demo Selección', 'Prueba del módulo de evaluación de proveedores', 'Operaciones', 'Solicitud semilla para cotizaciones y evaluación comparativa', 'APROBADA', NOW(), NOW()
FROM users u
ORDER BY u.id
LIMIT 1;
