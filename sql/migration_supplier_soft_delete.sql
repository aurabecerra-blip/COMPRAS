ALTER TABLE suppliers
    ADD COLUMN deleted_at DATETIME NULL AFTER created_at;

CREATE INDEX idx_suppliers_deleted_at ON suppliers (deleted_at);
