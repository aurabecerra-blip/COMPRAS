-- Asignación de líder a proveedores para filtrar evaluación por responsable.

SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'suppliers'
      AND column_name = 'leader_user_id'
);

SET @add_col_sql = IF(
    @col_exists = 0,
    'ALTER TABLE suppliers ADD COLUMN leader_user_id INT NULL AFTER phone',
    'SELECT 1'
);
PREPARE add_col_stmt FROM @add_col_sql;
EXECUTE add_col_stmt;
DEALLOCATE PREPARE add_col_stmt;

SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'suppliers'
      AND constraint_name = 'fk_suppliers_leader_user'
      AND constraint_type = 'FOREIGN KEY'
);

SET @add_fk_sql = IF(
    @fk_exists = 0,
    'ALTER TABLE suppliers ADD CONSTRAINT fk_suppliers_leader_user FOREIGN KEY (leader_user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE add_fk_stmt FROM @add_fk_sql;
EXECUTE add_fk_stmt;
DEALLOCATE PREPARE add_fk_stmt;
