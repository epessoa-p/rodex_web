-- ============================================================
--  Rodex — Formas de pago por empresa
--  · payment_methods: formas de cobro que ofrece el POS y los demás cobros
--    (lista separada por comas: efectivo,qr,transferencia,tarjeta). Por
--    defecto solo efectivo: nada cambia hasta que la empresa las active.
--  · expenses_use_other_methods: 1 = lo cobrado por QR/transferencia/tarjeta
--    también sirve para pagar gastos desde la caja (se usa el efectivo
--    primero y el resto se anota con el otro medio). Por defecto 1: es lo
--    que se podía hacer hasta ahora.
--  Seguro de re-ejecutar: valida que las columnas no existan.
-- ============================================================

SET @col_pm := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'payment_methods'
);
SET @sql := IF(@col_pm = 0,
    'ALTER TABLE `companies` ADD COLUMN `payment_methods` VARCHAR(100) NOT NULL DEFAULT ''efectivo''',
    'SELECT "companies.payment_methods ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exp := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'expenses_use_other_methods'
);
SET @sql := IF(@col_exp = 0,
    'ALTER TABLE `companies` ADD COLUMN `expenses_use_other_methods` TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT "companies.expenses_use_other_methods ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Empresas que YA cobran por QR/transferencia/tarjeta (p. ej. en el Servicio
-- rápido del taller): se les activan esas formas para que nada deje de
-- funcionar al desplegar. Solo toca las que siguen en el valor por defecto,
-- así que se puede volver a correr sin pisar lo que cada empresa configuró.
UPDATE `companies` c
SET c.`payment_methods` = CONCAT_WS(',',
    'efectivo',
    IF(EXISTS(SELECT 1 FROM `cash_movements` m WHERE m.`company_id` = c.`id` AND m.`type` = 'income' AND m.`method` = 'qr'), 'qr', NULL),
    IF(EXISTS(SELECT 1 FROM `cash_movements` m WHERE m.`company_id` = c.`id` AND m.`type` = 'income' AND m.`method` = 'transferencia'), 'transferencia', NULL),
    IF(EXISTS(SELECT 1 FROM `cash_movements` m WHERE m.`company_id` = c.`id` AND m.`type` = 'income' AND m.`method` = 'tarjeta'), 'tarjeta', NULL)
)
WHERE c.`payment_methods` = 'efectivo';
