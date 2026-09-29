-- ============================================================
--  Rodex — Configuración del POS por empresa
--  · pos_rounding_step: paso del botón "Redondear" del total en el POS
--    (0.50 o 1). Por defecto 0.50 (empresas nuevas y existentes).
--  · allow_credit_sales: muestra/permite "A Crédito" en el POS y en el
--    formulario de venta. Por defecto 1: las empresas existentes siguen
--    vendiendo a crédito como hasta ahora; quien no lo use lo apaga.
--  Seguro de re-ejecutar: valida que las columnas no existan.
-- ============================================================

SET @col_round := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'pos_rounding_step'
);
SET @sql := IF(@col_round = 0,
    'ALTER TABLE `companies` ADD COLUMN `pos_rounding_step` DECIMAL(4,2) NOT NULL DEFAULT 0.50',
    'SELECT "companies.pos_rounding_step ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_credit := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'allow_credit_sales'
);
SET @sql := IF(@col_credit = 0,
    'ALTER TABLE `companies` ADD COLUMN `allow_credit_sales` TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT "companies.allow_credit_sales ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
