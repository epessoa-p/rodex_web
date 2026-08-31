-- =====================================================================
--  Rodex — Seguimiento público de la OT (work_orders.public_token)
--  Genera un enlace público para que el cliente siga el estado de su
--  orden de trabajo sin iniciar sesión. El token va en la URL (/ot/{token}).
--
--  Ejecutar UNA sola vez en producción. Idempotente. MySQL 8.0+.
-- =====================================================================

-- Agrega la columna solo si no existe (evita error al re-ejecutar).
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'work_orders'
      AND COLUMN_NAME = 'public_token');
SET @sql := IF(@col = 0,
    'ALTER TABLE `work_orders`
        ADD COLUMN `public_token` VARCHAR(40) NULL AFTER `code`,
        ADD UNIQUE KEY `work_orders_public_token_unique` (`public_token`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
