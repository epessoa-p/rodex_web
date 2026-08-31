-- ============================================================
--  Rodex — Comentario por foto de la OT (work_order_photos.caption)
--  Permite anotar cada foto (ej. "cambiar esta pieza gastada").
--  Ejecutar UNA sola vez. Idempotente. MySQL 8.0+.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_order_photos' AND COLUMN_NAME = 'caption');
SET @sql := IF(@col = 0,
    'ALTER TABLE `work_order_photos` ADD COLUMN `caption` VARCHAR(500) NULL AFTER `file_name`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
