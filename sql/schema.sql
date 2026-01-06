CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

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
    description TEXT,
    status ENUM('BORRADOR','ENVIADA','EN_APROBACION','APROBADA','RECHAZADA') NOT NULL DEFAULT 'BORRADOR',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (requester_id) REFERENCES users(id)
);

CREATE TABLE pr_items (
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
    notes VARCHAR(255),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    supplier_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
    close_reason TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE po_items (
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
    created_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    description VARCHAR(200) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (receipt_id) REFERENCES receipts(id) ON DELETE CASCADE
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    invoice_number VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    registered_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (registered_by) REFERENCES users(id)
);

CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id),
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

INSERT INTO roles (name) VALUES
('admin'), ('requester'), ('approver'), ('buyer'), ('receiver'), ('accountant');

INSERT INTO users (name, email, role, password_hash, created_at) VALUES
('Administrador', 'admin@aos.com', 'admin', '$2y$12$QwXRK91/HPm0QYeCtNJkRezlaA1LAO.qWms4JfEQ7QJfJGMrrY6Mu', NOW());

INSERT INTO settings (`key`, value) VALUES
('company_name', 'AOS'),
('brand_logo_path', '/public/assets/aos-logo.svg');
