-- Esta migración aplica únicamente a la tabla de reevaluación histórica de proveedores.
-- No modifica las tablas del módulo de selección de proveedor en compras
-- (provider_selection_evaluations/provider_selection_scores).

SET @current_schema = DATABASE();

SET @supplier_evaluations_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = @current_schema
      AND table_name = 'supplier_evaluations'
);

SET @add_observations_sql = IF(
    @supplier_evaluations_exists = 1
    AND (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = @current_schema
          AND table_name = 'supplier_evaluations'
          AND column_name = 'observations'
    ) = 0,
    'ALTER TABLE supplier_evaluations ADD COLUMN observations TEXT NULL AFTER status_label',
    'SELECT 1'
);
PREPARE add_observations_stmt FROM @add_observations_sql;
EXECUTE add_observations_stmt;
DEALLOCATE PREPARE add_observations_stmt;

SET @add_pdf_path_sql = IF(
    @supplier_evaluations_exists = 1
    AND (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = @current_schema
          AND table_name = 'supplier_evaluations'
          AND column_name = 'pdf_path'
    ) = 0,
    'ALTER TABLE supplier_evaluations ADD COLUMN pdf_path VARCHAR(255) NULL AFTER observations',
    'SELECT 1'
);
PREPARE add_pdf_path_stmt FROM @add_pdf_path_sql;
EXECUTE add_pdf_path_stmt;
DEALLOCATE PREPARE add_pdf_path_stmt;
