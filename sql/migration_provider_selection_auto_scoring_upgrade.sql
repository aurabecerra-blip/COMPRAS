-- Upgrade idempotente para scoring automático de selección de proveedor.
-- Si la tabla base no existe, no falla con #1146 y muestra el paso previo requerido.

SET @provider_quotes_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'provider_quotes'
);

SET @upgrade_sql := IF(
    @provider_quotes_exists = 0,
    'SELECT ''Tabla provider_quotes no existe. Ejecute primero: sql/migration_provider_selection_module.sql'' AS warning_message',
    "ALTER TABLE provider_quotes
        ADD COLUMN IF NOT EXISTS experiencia ENUM('LT2','2TO5','GT5') NOT NULL DEFAULT 'LT2' AFTER forma_pago,
        ADD COLUMN IF NOT EXISTS entrega ENUM('MAYOR_10','IGUAL_10','MENOR_5','NA') NOT NULL DEFAULT 'IGUAL_10' AFTER experiencia,
        ADD COLUMN IF NOT EXISTS entrega_na_result ENUM('CUMPLE','NO_CUMPLE') NOT NULL DEFAULT 'NO_CUMPLE' AFTER entrega,
        ADD COLUMN IF NOT EXISTS descuento ENUM('SI','NO') NOT NULL DEFAULT 'NO' AFTER entrega_na_result,
        ADD COLUMN IF NOT EXISTS certificaciones ENUM('NINGUNA','UNA','DOS_MAS') NOT NULL DEFAULT 'NINGUNA' AFTER descuento"
);

PREPARE stmt FROM @upgrade_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
