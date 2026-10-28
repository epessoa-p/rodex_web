-- ============================================================
--  Rodex — Ubicación del producto por almacén + datos del POS
--  · product_locations: ubicación física de un producto en cada almacén
--    (ej. "PA5-VI2" = pasillo 5, vitrina 2). Una por producto y almacén.
--  · companies.pos_card_fields: qué datos se ven en cada producto del POS
--    (code,brand,category,models,location,stock). NULL = valor por defecto
--    (code,brand,models,location).
--  Seguro de re-ejecutar.
-- ============================================================

CREATE TABLE IF NOT EXISTS `product_locations` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   BIGINT UNSIGNED NOT NULL,
    `product_id`   BIGINT UNSIGNED NOT NULL,
    `warehouse_id` BIGINT UNSIGNED NOT NULL,
    `location`     VARCHAR(40) NOT NULL,
    `created_at`   TIMESTAMP NULL DEFAULT NULL,
    `updated_at`   TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pl_product_warehouse` (`product_id`, `warehouse_id`),
    KEY `pl_company_wh_loc` (`company_id`, `warehouse_id`, `location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @c1 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'pos_card_fields');
SET @sql := IF(@c1 = 0,
    'ALTER TABLE `companies` ADD COLUMN `pos_card_fields` VARCHAR(120) NULL',
    'SELECT "companies.pos_card_fields ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
