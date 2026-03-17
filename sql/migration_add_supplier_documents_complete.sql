-- Permite gestionar manualmente si el proveedor tiene documentación completa.

SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'suppliers'
      AND column_name = 'documents_complete'
);

SET @add_col_sql = IF(
    @col_exists = 0,
    'ALTER TABLE suppliers ADD COLUMN documents_complete TINYINT(1) NOT NULL DEFAULT 0 AFTER leader_user_id',
    'SELECT 1'
);
PREPARE add_col_stmt FROM @add_col_sql;
EXECUTE add_col_stmt;
DEALLOCATE PREPARE add_col_stmt;
