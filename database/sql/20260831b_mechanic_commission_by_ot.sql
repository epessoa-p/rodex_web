-- ============================================================
--  Rodex — Liquidación de comisiones por OT
--  Marca cada OT entregada como pagada/pendiente al mecánico y
--  congela la comisión liquidada.
--
--  CORRER DESPUÉS de 20260831_mechanic_payments.sql (referencia
--  esa tabla). Idempotente. MySQL 8.0+.
-- ============================================================

-- 1) work_orders.mechanic_payment_id (pagada ⇔ no nulo) + FK + índice
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'mechanic_payment_id');
SET @sql := IF(@col = 0,
    'ALTER TABLE `work_orders` ADD COLUMN `mechanic_payment_id` BIGINT UNSIGNED NULL AFTER `cash_register_session_id`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders' AND INDEX_NAME = 'idx_wo_mech_payment');
SET @sql := IF(@idx = 0,
    'ALTER TABLE `work_orders` ADD KEY `idx_wo_mech_payment` (`mechanic_payment_id`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders' AND CONSTRAINT_NAME = 'wo_mech_payment_fk');
SET @sql := IF(@fk = 0,
    'ALTER TABLE `work_orders` ADD CONSTRAINT `wo_mech_payment_fk` FOREIGN KEY (`mechanic_payment_id`) REFERENCES `mechanic_payments`(`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) work_orders.commission_amount (comisión congelada al liquidar)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'commission_amount');
SET @sql := IF(@col = 0,
    'ALTER TABLE `work_orders` ADD COLUMN `commission_amount` DECIMAL(15,2) NULL AFTER `mechanic_payment_id`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
