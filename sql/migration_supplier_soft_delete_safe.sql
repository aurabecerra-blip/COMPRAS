-- Detecta la tabla real de proveedores y agrega deleted_at solo si no existe.
SET @supplier_table := (
    SELECT table_name
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name IN ('suppliers', 'proveedores')
    ORDER BY FIELD(table_name, 'suppliers', 'proveedores')
    LIMIT 1
);

SET @has_deleted_at := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = @supplier_table
      AND column_name = 'deleted_at'
);

SET @ddl := IF(
    @supplier_table IS NULL,
    'SELECT "No existe tabla de proveedores (suppliers/proveedores)" AS error_msg',
    IF(
        @has_deleted_at = 0,
        CONCAT('ALTER TABLE `', @supplier_table, '` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'),
        'SELECT "deleted_at ya existe" AS info_msg'
    )
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @describe_sql := IF(
    @supplier_table IS NULL,
    'SELECT "No hay tabla para DESCRIBE" AS error_msg',
    CONCAT('DESCRIBE `', @supplier_table, '`')
);
PREPARE stmt2 FROM @describe_sql;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
