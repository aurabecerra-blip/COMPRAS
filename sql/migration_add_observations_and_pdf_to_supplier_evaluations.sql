ALTER TABLE supplier_evaluations
    ADD COLUMN observations TEXT NULL AFTER status_label,
    ADD COLUMN pdf_path VARCHAR(255) NULL AFTER observations;
