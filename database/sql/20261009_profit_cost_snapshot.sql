-- ============================================================
--  Rodex — Costo guardado al vender (reporte de Ganancias)
--  · sale_items.unit_cost        costo del producto al momento de la venta
--  · work_order_parts.unit_cost  costo del repuesto al agregarlo a la OT
--  NULL = línea anterior a este cambio: el reporte usa el costo actual del
--  producto y la marca como "estimada". No se reescriben datos viejos.
--  Seguro de re-ejecutar: valida que las columnas no existan.
-- ============================================================

SET @c1 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_items' AND COLUMN_NAME = 'unit_cost');
SET @sql := IF(@c1 = 0,
    'ALTER TABLE `sale_items` ADD COLUMN `unit_cost` DECIMAL(12,2) NULL AFTER `unit_price`',
    'SELECT "sale_items.unit_cost ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c2 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_order_parts' AND COLUMN_NAME = 'unit_cost');
SET @sql := IF(@c2 = 0,
    'ALTER TABLE `work_order_parts` ADD COLUMN `unit_cost` DECIMAL(12,2) NULL AFTER `unit_price`',
    'SELECT "work_order_parts.unit_cost ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
