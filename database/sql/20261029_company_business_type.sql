-- ============================================================
--  Rodex — Rubro de la empresa (companies.business_type)
--  Valores: moto, auto, lubricentro, none. Decide qué catálogos
--  se precargan en el alta. Las existentes quedan como 'moto'.
--  Seguro de re-ejecutar: valida existencia previa.
-- ============================================================

SET @exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'companies'
      AND COLUMN_NAME = 'business_type'
);

SET @sql := IF(@exists = 0,
    'ALTER TABLE `companies` ADD COLUMN `business_type` VARCHAR(20) NOT NULL DEFAULT ''moto'' AFTER `currency`',
    'SELECT "La columna business_type ya existe" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
