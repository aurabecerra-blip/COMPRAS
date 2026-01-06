CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    role VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE settings (
    `key` VARCHAR(50) PRIMARY KEY,
    value VARCHAR(255) NOT NULL
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact VARCHAR(150),
    email VARCHAR(150),
    phone VARCHAR(50),
    created_at DATETIME NOT NULL
);

CREATE TABLE purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    justification TEXT NOT NULL,
    area VARCHAR(150) NOT NULL,
    cost_center VARCHAR(100) NOT NULL,
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

INSERT INTO users (name, email, role, password_hash, created_at) VALUES
('Administrador', 'admin@aos.com', 'administrador', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfEQ7QJfJGMrrY6Mu', NOW());

INSERT INTO settings (`key`, value) VALUES
('company_name', 'AOS'),
('brand_logo_path', 'assets/aos-logo.svg'),
('brand_primary_color', '#0d6efd'),
('brand_accent_color', '#198754');
