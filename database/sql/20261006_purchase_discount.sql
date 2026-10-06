-- ============================================================
--  Rodex — Descuento del proveedor en compras y órdenes de compra
--  · discount: monto descontado sobre el subtotal (total = subtotal − discount + tax).
--    El descuento se reparte en el costo de cada producto al entrar al kardex.
--  Registros existentes quedan en 0 (nada cambia).
--  Seguro de re-ejecutar: valida que las columnas no existan.
-- ============================================================

SET @c1 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'discount');
SET @sql := IF(@c1 = 0,
    'ALTER TABLE `purchases` ADD COLUMN `discount` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `subtotal`',
    'SELECT "purchases.discount ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c2 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'discount');
SET @sql := IF(@c2 = 0,
    'ALTER TABLE `purchase_orders` ADD COLUMN `discount` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `subtotal`',
    'SELECT "purchase_orders.discount ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
