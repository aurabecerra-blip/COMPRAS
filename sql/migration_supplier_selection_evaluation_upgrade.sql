-- Ajustes para módulo "Evaluación de Selección de Proveedor"
ALTER TABLE supplier_selection_processes
    MODIFY status ENUM('BORRADOR', 'FINALIZADA', 'ANULADO') NOT NULL DEFAULT 'BORRADOR';

ALTER TABLE supplier_quotations
    ADD COLUMN purchase_request_id INT NULL AFTER selection_process_id,
    ADD COLUMN quote_number VARCHAR(80) NULL AFTER supplier_id;

ALTER TABLE supplier_quotations
    ADD CONSTRAINT fk_supplier_quotations_pr FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    ADD INDEX idx_supplier_quotations_pr (purchase_request_id);

UPDATE supplier_quotations q
INNER JOIN supplier_selection_processes p ON p.id = q.selection_process_id
SET q.purchase_request_id = p.purchase_request_id
WHERE q.purchase_request_id IS NULL;

ALTER TABLE supplier_quotations
    MODIFY purchase_request_id INT NOT NULL;

CREATE TABLE IF NOT EXISTS supplier_quotation_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    file_size INT NULL,
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_quotation_files_quotation FOREIGN KEY (quotation_id) REFERENCES supplier_quotations(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_quotation_files_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_supplier_quotation_files_quotation (quotation_id)
);
