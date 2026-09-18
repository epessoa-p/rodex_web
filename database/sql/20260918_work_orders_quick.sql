-- ============================================================
--  Rodex — "Servicio rápido" (OT sin cliente ni vehículo)
--  · work_orders.client_id / vehicle_id pasan a NULL (si aún fueran NOT NULL;
--    rentals_v1.sql ya lo hacía, esto cubre instalaciones sin alquileres).
--  · is_quick: marca la OT creada y cobrada en un solo paso.
--  · quick_vehicle: texto libre del vehículo cuando no hay vehicle_id.
--  Seguro de re-ejecutar: valida existencia/nulabilidad previa.
-- ============================================================

SET @nn_client := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders'
      AND COLUMN_NAME = 'client_id' AND IS_NULLABLE = 'NO'
);
SET @sql := IF(@nn_client > 0,
    'ALTER TABLE `work_orders` MODIFY COLUMN `client_id` BIGINT UNSIGNED NULL',
    'SELECT "work_orders.client_id ya admite NULL" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @nn_vehicle := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders'
      AND COLUMN_NAME = 'vehicle_id' AND IS_NULLABLE = 'NO'
);
SET @sql := IF(@nn_vehicle > 0,
    'ALTER TABLE `work_orders` MODIFY COLUMN `vehicle_id` BIGINT UNSIGNED NULL',
    'SELECT "work_orders.vehicle_id ya admite NULL" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_quick := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'is_quick'
);
SET @sql := IF(@col_quick = 0,
    'ALTER TABLE `work_orders` ADD COLUMN `is_quick` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`, ADD KEY `idx_work_orders_quick` (`company_id`, `is_quick`)',
    'SELECT "work_orders.is_quick ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_qv := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'quick_vehicle'
);
SET @sql := IF(@col_qv = 0,
    'ALTER TABLE `work_orders` ADD COLUMN `quick_vehicle` VARCHAR(80) NULL AFTER `vehicle_id`',
    'SELECT "work_orders.quick_vehicle ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
