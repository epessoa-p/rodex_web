-- ============================================================
--  Rodex — Orden de los tabs del dashboard móvil (por empresa).
--  Configurable desde "Mi empresa". Ejecutar UNA sola vez.
--  Idempotente. MySQL 8.0+.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'dashboard_order');
SET @sql := IF(@col = 0,
    'ALTER TABLE `companies` ADD COLUMN `dashboard_order` VARCHAR(60) NOT NULL DEFAULT ''ventas,taller,compras'' AFTER `tracking_link_days`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
