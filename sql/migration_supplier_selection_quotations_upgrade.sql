-- Upgrade del módulo: Selección de Proveedor por Cotizaciones (sin módulo de reevaluación)

ALTER TABLE supplier_selection_processes
    MODIFY status ENUM('BORRADOR', 'EN_EVALUACION', 'SELECCIONADO', 'ANULADO') NOT NULL DEFAULT 'BORRADOR',
    CHANGE COLUMN selection_pdf_path acta_pdf_url VARCHAR(255) NULL,
    CHANGE COLUMN winner_justification justification_text TEXT NULL;

ALTER TABLE supplier_quotations
    ADD COLUMN valor_subtotal DECIMAL(14,2) NULL AFTER quotation_date,
    ADD COLUMN valor_total DECIMAL(14,2) NULL AFTER valor_subtotal,
    ADD COLUMN ofrece_descuento TINYINT(1) NOT NULL DEFAULT 0 AFTER observations,
    ADD COLUMN tipo_descuento ENUM('PORCENTAJE','VALOR') NULL AFTER ofrece_descuento,
    ADD COLUMN descuento_valor DECIMAL(14,2) NULL AFTER tipo_descuento,
    ADD COLUMN experiencia_anios INT NOT NULL DEFAULT 0 AFTER descuento_valor,
    ADD COLUMN certificaciones_tecnicas TINYINT(1) NOT NULL DEFAULT 0 AFTER experiencia_anios,
    ADD COLUMN certificaciones_comerciales TINYINT(1) NOT NULL DEFAULT 0 AFTER certificaciones_tecnicas,
    ADD COLUMN lista_certificaciones TEXT NULL AFTER certificaciones_comerciales,
    ADD COLUMN archivo_cotizacion_url VARCHAR(255) NULL AFTER lista_certificaciones,
    ADD COLUMN archivo_soporte_experiencia_url VARCHAR(255) NULL AFTER archivo_cotizacion_url,
    ADD COLUMN archivo_certificaciones_url VARCHAR(255) NULL AFTER archivo_soporte_experiencia_url,
    ADD COLUMN evaluacion_pago ENUM('MUY_FAVORABLES', 'ACEPTABLES', 'POCO_FAVORABLES') NOT NULL DEFAULT 'ACEPTABLES' AFTER payment_terms,
    ADD COLUMN evaluacion_postventa ENUM('CUMPLE_TOTAL', 'CUMPLE_PARCIAL', 'NO_CUMPLE') NOT NULL DEFAULT 'CUMPLE_PARCIAL' AFTER warranty;

UPDATE supplier_quotations
SET valor_total = total_value,
    valor_subtotal = total_value,
    archivo_cotizacion_url = evidence_file_path
WHERE valor_total IS NULL;

ALTER TABLE supplier_quotations
    MODIFY valor_subtotal DECIMAL(14,2) NOT NULL,
    MODIFY valor_total DECIMAL(14,2) NOT NULL,
    MODIFY archivo_cotizacion_url VARCHAR(255) NOT NULL;

ALTER TABLE supplier_selection_scores
    DROP INDEX uq_supplier_selection_scores_quotation,
    ADD COLUMN criterion_code VARCHAR(80) NOT NULL DEFAULT 'TOTAL' AFTER supplier_id,
    ADD COLUMN criterion_name VARCHAR(120) NOT NULL DEFAULT 'Total' AFTER criterion_code,
    ADD COLUMN criterion_weight DECIMAL(6,2) NOT NULL DEFAULT 0.00 AFTER criterion_name,
    ADD COLUMN score_value DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER criterion_weight,
    ADD COLUMN input_data_json JSON NULL AFTER score_value,
    ADD COLUMN formula_applied TEXT NULL AFTER input_data_json,
    ADD INDEX idx_supplier_selection_scores_criterion (criterion_code);
