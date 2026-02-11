-- Módulo A: Reevaluación de proveedor (cabecera + detalle)
CREATE TABLE IF NOT EXISTS provider_reevaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    provider_name VARCHAR(150) NOT NULL,
    provider_nit VARCHAR(50) NULL,
    service_provided VARCHAR(255) NULL,
    evaluation_date DATE NOT NULL,
    evaluator_user_id INT NOT NULL,
    observations TEXT NULL,
    total_score INT NOT NULL,
    pdf_path VARCHAR(255) NULL,
    email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    email_error TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_provider_reevaluations_provider FOREIGN KEY (provider_id) REFERENCES suppliers(id),
    CONSTRAINT fk_provider_reevaluations_evaluator FOREIGN KEY (evaluator_user_id) REFERENCES users(id),
    INDEX idx_provider_reevaluations_provider (provider_id),
    INDEX idx_provider_reevaluations_date (evaluation_date),
    INDEX idx_provider_reevaluations_evaluator (evaluator_user_id)
);

CREATE TABLE IF NOT EXISTS provider_reevaluation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reevaluation_id INT NOT NULL,
    criterion_code VARCHAR(80) NOT NULL,
    criterion_name VARCHAR(255) NOT NULL,
    selected_option VARCHAR(120) NOT NULL,
    selected_label VARCHAR(255) NOT NULL,
    extra_value INT NULL,
    item_score INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_provider_reevaluation_items_header FOREIGN KEY (reevaluation_id) REFERENCES provider_reevaluations(id) ON DELETE CASCADE,
    INDEX idx_provider_reevaluation_items_header (reevaluation_id),
    INDEX idx_provider_reevaluation_items_criterion (criterion_code)
);

-- Módulo B: Proceso de selección de proveedor por solicitud
CREATE TABLE IF NOT EXISTS supplier_selection_processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id INT NOT NULL,
    evaluator_user_id INT NOT NULL,
    status ENUM('BORRADOR', 'EN_EVALUACION', 'SELECCIONADO', 'ANULADO') NOT NULL DEFAULT 'BORRADOR',
    winner_supplier_id INT NULL,
    winner_justification TEXT NULL,
    observations TEXT NULL,
    selection_pdf_path VARCHAR(255) NULL,
    selected_at DATETIME NULL,
    annulled_reason TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_selection_processes_pr FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_selection_processes_evaluator FOREIGN KEY (evaluator_user_id) REFERENCES users(id),
    CONSTRAINT fk_supplier_selection_processes_winner FOREIGN KEY (winner_supplier_id) REFERENCES suppliers(id),
    UNIQUE KEY uq_supplier_selection_processes_pr (purchase_request_id),
    INDEX idx_supplier_selection_processes_status (status)
);

CREATE TABLE IF NOT EXISTS supplier_quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selection_process_id INT NOT NULL,
    supplier_id INT NOT NULL,
    quotation_date DATE NOT NULL,
    total_value DECIMAL(14,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'COP',
    delivery_term_days INT NOT NULL,
    payment_terms VARCHAR(150) NOT NULL,
    warranty VARCHAR(150) NOT NULL,
    technical_compliance ENUM('CUMPLE', 'PARCIAL', 'NO_CUMPLE') NOT NULL DEFAULT 'CUMPLE',
    observations TEXT NULL,
    evidence_file_path VARCHAR(255) NOT NULL,
    evidence_original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    file_size INT NULL,
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_quotations_process FOREIGN KEY (selection_process_id) REFERENCES supplier_selection_processes(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_quotations_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_quotations_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_supplier_quotations_process (selection_process_id),
    INDEX idx_supplier_quotations_supplier (supplier_id),
    UNIQUE KEY uq_supplier_quotations_process_supplier (selection_process_id, supplier_id)
);

CREATE TABLE IF NOT EXISTS supplier_selection_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selection_process_id INT NOT NULL,
    quotation_id INT NOT NULL,
    supplier_id INT NOT NULL,
    price_score DECIMAL(8,2) NOT NULL,
    delivery_score DECIMAL(8,2) NOT NULL,
    payment_score DECIMAL(8,2) NOT NULL,
    warranty_score DECIMAL(8,2) NOT NULL,
    technical_score DECIMAL(8,2) NOT NULL,
    total_score DECIMAL(8,2) NOT NULL,
    rank_position INT NULL,
    is_winner TINYINT(1) NOT NULL DEFAULT 0,
    details_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_selection_scores_process FOREIGN KEY (selection_process_id) REFERENCES supplier_selection_processes(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_selection_scores_quotation FOREIGN KEY (quotation_id) REFERENCES supplier_quotations(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_selection_scores_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    UNIQUE KEY uq_supplier_selection_scores_quotation (quotation_id),
    INDEX idx_supplier_selection_scores_process (selection_process_id),
    INDEX idx_supplier_selection_scores_rank (rank_position)
);
