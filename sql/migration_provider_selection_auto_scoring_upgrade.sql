ALTER TABLE provider_quotes
    ADD COLUMN experiencia ENUM('LT2','2TO5','GT5') NOT NULL DEFAULT 'LT2' AFTER forma_pago,
    ADD COLUMN entrega ENUM('MAYOR_10','IGUAL_10','MENOR_5','NA') NOT NULL DEFAULT 'IGUAL_10' AFTER experiencia,
    ADD COLUMN entrega_na_result ENUM('CUMPLE','NO_CUMPLE') NOT NULL DEFAULT 'NO_CUMPLE' AFTER entrega,
    ADD COLUMN descuento ENUM('SI','NO') NOT NULL DEFAULT 'NO' AFTER entrega_na_result,
    ADD COLUMN certificaciones ENUM('NINGUNA','UNA','DOS_MAS') NOT NULL DEFAULT 'NINGUNA' AFTER descuento;
