CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    role VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL
);

CREATE TABLE settings (
    `key` VARCHAR(50) PRIMARY KEY,
    value VARCHAR(255) NOT NULL
);


CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    nit VARCHAR(50) NOT NULL,
    logo_path VARCHAR(255) NOT NULL,
    primary_color VARCHAR(7) NOT NULL DEFAULT '#1E3A8A',
    secondary_color VARCHAR(7) NOT NULL DEFAULT '#F8C8D8',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE notification_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    channel VARCHAR(50) NOT NULL DEFAULT 'email',
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE notification_type_roles (
    notification_type_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    PRIMARY KEY (notification_type_id, role),
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(id) ON DELETE CASCADE
);

CREATE TABLE notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type_id INT NOT NULL,
    channel VARCHAR(50) NOT NULL,
    recipient VARCHAR(150) NOT NULL,
    status ENUM('enviado','error') NOT NULL,
    error_message VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(id)
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    nit VARCHAR(50) NULL,
    service VARCHAR(255) NULL,
    contact VARCHAR(150),
    email VARCHAR(150),
    phone VARCHAR(50),
    created_at DATETIME NOT NULL
);

CREATE TABLE supplier_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    evaluator_user_id INT NOT NULL,
    evaluation_date DATETIME NOT NULL,
    total_score INT NOT NULL,
    status_label ENUM('Aprobado','Condicional','No aprobado') NOT NULL,
    observations TEXT NULL,
    pdf_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (evaluator_user_id) REFERENCES users(id)
);

CREATE TABLE supplier_evaluation_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    criterion_code VARCHAR(80) NOT NULL,
    criterion_name VARCHAR(255) NOT NULL,
    option_key VARCHAR(80) NOT NULL,
    option_label VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    notes VARCHAR(255) NULL,
    FOREIGN KEY (evaluation_id) REFERENCES supplier_evaluations(id) ON DELETE CASCADE
);

CREATE TABLE purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    tracking_code VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    justification TEXT NOT NULL,
    area VARCHAR(150) NOT NULL,
    description TEXT NULL,
    status ENUM('BORRADOR','ENVIADA','APROBADA','RECHAZADA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
    rejection_reason TEXT NULL,
    selected_supplier_id INT NULL,
    selection_notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (selected_supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE purchase_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    description VARCHAR(200) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE
);

CREATE TABLE quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    supplier_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    lead_time_days INT NOT NULL,
    notes VARCHAR(255),
    pdf_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    supplier_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('CREADA','ENVIADA_A_PROVEEDOR','RECIBIDA_PARCIAL','RECIBIDA_TOTAL','CERRADA') NOT NULL DEFAULT 'CREADA',
    close_reason TEXT,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    description VARCHAR(200) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
);

CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    received_by INT NOT NULL,
    evidence_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    po_item_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (receipt_id) REFERENCES receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES purchase_order_items(id) ON DELETE CASCADE
);

CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    detail_json JSON NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);


INSERT INTO companies (name, nit, logo_path, primary_color, secondary_color, created_at, updated_at) VALUES
('AOS', '900.635.119-8', 'assets/logo_aos.png', '#1E3A8A', '#F8C8D8', NOW(), NOW());

INSERT INTO settings (`key`, value) VALUES
('company_name', 'AOS'),
('company_nit', '900.635.119-8'),
('brand_logo_path', 'assets/logo_aos.png'),
('brand_primary_color', '#1E3A8A'),
('brand_accent_color', '#F8C8D8'),
('active_company_id', '1'),
('form_areas', 'Operaciones,Finanzas,TI,Calidad'),
('form_cost_centers', 'CC-001,CC-002,CC-003'),
('notifications_enabled', '0'),
('notifications_email_enabled', '0'),
('notifications_smtp_host', ''),
('notifications_smtp_port', ''),
('notifications_smtp_security', 'tls'),
('notifications_smtp_user', ''),
('notifications_smtp_password', ''),
('notifications_from_email', ''),
('notifications_from_name', ''),
('notifications_test_email', '');

INSERT INTO notification_types (code, name, description, channel, is_active) VALUES
('purchase_request_created', 'Solicitud creada', 'Creación de una solicitud de compra', 'email', 1),
('purchase_request_sent', 'Solicitud enviada', 'Envío de solicitud a aprobación', 'email', 1),
('purchase_request_approved', 'Solicitud aprobada', 'Aprobación de una solicitud', 'email', 1),
('purchase_request_rejected', 'Solicitud rechazada', 'Rechazo de una solicitud', 'email', 1),
('supplier_evaluation_completed', 'Evaluación de proveedor completada', 'Envío del resultado de evaluación al proveedor', 'email', 1),
('test_email', 'Correo de prueba', 'Mensaje de prueba para SMTP', 'email', 1);

INSERT INTO notification_type_roles (notification_type_id, role)
SELECT id, 'aprobador' FROM notification_types WHERE code = 'purchase_request_sent';
INSERT INTO notification_type_roles (notification_type_id, role)
SELECT id, 'compras' FROM notification_types WHERE code IN ('purchase_request_approved', 'purchase_request_rejected');
