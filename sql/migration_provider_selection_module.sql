CREATE TABLE IF NOT EXISTS provider_quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    provider_id INT NOT NULL,
    tipo_compra ENUM('BIENES','SERVICIOS','SERVICIOS_TECNICOS') NOT NULL,
    valor DECIMAL(18,2) NOT NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'COP',
    plazo_entrega_dias INT NOT NULL,
    forma_pago ENUM('CONTADO','CREDICONTADO','CREDITO_30_MAS','NA') NOT NULL,
    experiencia ENUM('LT2','2TO5','GT5') NOT NULL,
    entrega ENUM('MAYOR_10','IGUAL_10','MENOR_5','NA') NOT NULL,
    entrega_na_result ENUM('CUMPLE','NO_CUMPLE') NOT NULL DEFAULT 'NO_CUMPLE',
    descuento ENUM('SI','NO') NOT NULL DEFAULT 'NO',
    certificaciones ENUM('NINGUNA','UNA','DOS_MAS') NOT NULL DEFAULT 'NINGUNA',
    recotizacion TINYINT(1) NOT NULL DEFAULT 0,
    notas TEXT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_provider_quotes_pr (purchase_request_id),
    INDEX idx_provider_quotes_provider (provider_id)
);

CREATE TABLE IF NOT EXISTS provider_quote_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (quote_id) REFERENCES provider_quotes(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_quote_files_quote (quote_id)
);

CREATE TABLE IF NOT EXISTS provider_selection_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    status ENUM('DRAFT','CLOSED') NOT NULL DEFAULT 'DRAFT',
    closed_at DATETIME NULL,
    closed_by INT NULL,
    winner_provider_id INT NULL,
    tie_break_reason TEXT NULL,
    observations TEXT NULL,
    pdf_path TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (closed_by) REFERENCES users(id),
    FOREIGN KEY (winner_provider_id) REFERENCES suppliers(id),
    UNIQUE KEY uk_provider_eval_pr (purchase_request_id)
);

CREATE TABLE IF NOT EXISTS provider_selection_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    provider_id INT NOT NULL,
    experiencia_score INT NOT NULL DEFAULT 0,
    forma_pago_score INT NOT NULL DEFAULT 0,
    entrega_score INT NOT NULL DEFAULT 0,
    descuento_score INT NOT NULL DEFAULT 0,
    certificaciones_score INT NOT NULL DEFAULT 0,
    precios_score INT NOT NULL DEFAULT 0,
    total_score INT NOT NULL DEFAULT 0,
    criterio_detalle_json JSON NULL,
    observations TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (evaluation_id) REFERENCES provider_selection_evaluations(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES suppliers(id),
    UNIQUE KEY uk_eval_provider (evaluation_id, provider_id)
);
