-- ============================================================
--  Rodex — Agregar columna 'placa' (opcional) a moto_units
--  Placa de la unidad registrada al crearla (distinta de
--  'assigned_plate', que se asigna en la entrega).
--  Seguro de re-ejecutar: valida existencia previa.
-- ============================================================

SET @exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'moto_units'
      AND COLUMN_NAME = 'placa'
);

SET @sql := IF(@exists = 0,
    'ALTER TABLE `moto_units` ADD COLUMN `placa` VARCHAR(20) NULL AFTER `color`',
    'SELECT "La columna placa ya existe" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
