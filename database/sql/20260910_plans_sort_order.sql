-- ============================================================
--  Rodex — Orden manual de los planes (listado de super_admin).
--  Agrega `plans.sort_order` y rellena el orden inicial según el
--  precio actual, para no alterar cómo se ven hoy los planes.
--  Ejecutar UNA sola vez. Idempotente. MySQL 8.0+.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plans' AND COLUMN_NAME = 'sort_order');

-- 1) Columna de orden (0 = sin asignar; el listado ordena por sort_order, luego precio).
SET @sql := IF(@col = 0,
    'ALTER TABLE `plans` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `active`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Orden inicial = el que ya se veía (por precio asc). Solo la primera vez:
--    si la columna ya existía, NO se toca el orden que el operador haya definido.
SET @sql := IF(@col = 0,
    'UPDATE `plans` p
        JOIN (SELECT id, ROW_NUMBER() OVER (ORDER BY price ASC, id ASC) AS rn FROM `plans`) r
          ON r.id = p.id
        SET p.sort_order = r.rn',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
