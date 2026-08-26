-- ============================================================
--  Rodex — Inventario v2
--  Código en productos y categorías + campo "moto" en productos
-- ============================================================

ALTER TABLE `products`
    ADD COLUMN `code` VARCHAR(50) NULL AFTER `sku`,
    ADD COLUMN `moto` VARCHAR(255) NULL AFTER `compatible_models`;

ALTER TABLE `products`
    ADD INDEX `idx_products_code` (`company_id`, `code`);

ALTER TABLE `product_categories`
    ADD COLUMN `code` VARCHAR(30) NULL AFTER `name`;
