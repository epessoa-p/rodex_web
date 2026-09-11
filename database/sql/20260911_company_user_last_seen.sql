-- ============================================================
--  Rodex — Monitoreo de uso por empresa (super_admin).
--  Agrega `company_user.last_seen_at`: última vez que el usuario
--  entró a esa empresa (web o móvil). Lo actualiza el middleware
--  de tenant, como máximo una vez por hora por usuario+empresa.
--  Ejecutar UNA sola vez. Idempotente. MySQL 8.0+.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_user' AND COLUMN_NAME = 'last_seen_at');
SET @sql := IF(@col = 0,
    'ALTER TABLE `company_user` ADD COLUMN `last_seen_at` TIMESTAMP NULL DEFAULT NULL AFTER `active`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Índice para el MAX/COUNT por empresa del panel de uso.
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_user' AND INDEX_NAME = 'idx_company_user_seen');
SET @sql := IF(@idx = 0,
    'ALTER TABLE `company_user` ADD INDEX `idx_company_user_seen` (`company_id`, `last_seen_at`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
