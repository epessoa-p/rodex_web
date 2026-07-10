-- =====================================================================
--  Venta rápida (ítem libre / producto no registrado) en el POS
--  Modificación de estructura: tabla `sale_items`
--  Equivale a la migración:
--    2026_06_17_000001_add_direct_item_to_sale_items_table.php
--
--  Cambios:
--    1. Nueva columna `description` (nombre libre del ítem de venta rápida).
--    2. `product_id` pasa a ser NULLABLE (los ítems libres sin coincidencia
--       no se enlazan a un producto). Se recrea la FK existente.
--
--  Ejecutar UNA sola vez en producción. MySQL 8.0+.
-- =====================================================================

-- 1) Columna de nombre libre (después de product_id)
ALTER TABLE `sale_items`
    ADD COLUMN `description` VARCHAR(255) NULL AFTER `product_id`;

-- 2) product_id -> NULLABLE (soltar FK, modificar columna, recrear FK)
ALTER TABLE `sale_items`
    DROP FOREIGN KEY `sale_items_product_fk`;

ALTER TABLE `sale_items`
    MODIFY COLUMN `product_id` BIGINT UNSIGNED NULL;

ALTER TABLE `sale_items`
    ADD CONSTRAINT `sale_items_product_fk`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
