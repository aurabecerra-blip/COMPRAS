-- Ejecuta este script en tu base de datos COMPRAS para alinear el esquema.
-- 1) Agrega columna description en purchase_requests si no existe.
-- 2) Asegura columna description en purchase_request_items.
-- 3) Elimina cost_center de purchase_requests si aún existe.

SET @schema_name := DATABASE();

SET @has_pr_description := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'purchase_requests'
      AND COLUMN_NAME = 'description'
);
SET @sql := IF(
    @has_pr_description = 0,
    'ALTER TABLE purchase_requests ADD COLUMN description TEXT NULL AFTER title',
    'SELECT "purchase_requests.description ya existe"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_item_description := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'purchase_request_items'
      AND COLUMN_NAME = 'description'
);
SET @has_item_legacy_description := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'purchase_request_items'
      AND COLUMN_NAME = 'item_description'
);

SET @sql := IF(
    @has_item_description = 0 AND @has_item_legacy_description = 1,
    'ALTER TABLE purchase_request_items CHANGE COLUMN item_description description VARCHAR(200) NOT NULL',
    IF(
        @has_item_description = 0,
        'ALTER TABLE purchase_request_items ADD COLUMN description VARCHAR(200) NOT NULL AFTER purchase_request_id',
        'SELECT "purchase_request_items.description ya existe"'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_cost_center := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'purchase_requests'
      AND COLUMN_NAME = 'cost_center'
);
SET @sql := IF(
    @has_cost_center = 1,
    'ALTER TABLE purchase_requests DROP COLUMN cost_center',
    'SELECT "purchase_requests.cost_center no existe"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
