-- ============================================================
--  Rodex — Historial de cambios de precio y costo
--  · product_price_changes: cada cambio de precio o costo de un producto
--    (valor anterior, nuevo, quién, cuándo y desde dónde).
--  · sale_items.list_price: precio de lista que tenía el producto al vender
--    (para distinguir descuento de un precio cambiado).
--  · Permiso 'products.prices' (Cambiar precios y costos). Se asigna a todos
--    los roles que ya tienen 'products.edit', así nadie pierde nada al subir;
--    después el administrador lo quita en Cargos a quien no deba tenerlo.
--  Seguro de re-ejecutar.
-- ============================================================

CREATE TABLE IF NOT EXISTS `product_price_changes` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `product_id`  BIGINT UNSIGNED NOT NULL,
    `user_id`     BIGINT UNSIGNED NULL,
    `field`       VARCHAR(10)  NOT NULL,          -- price | cost
    `old_value`   DECIMAL(12,2) NULL,
    `new_value`   DECIMAL(12,2) NULL,
    `source`      VARCHAR(30)  NOT NULL DEFAULT 'sistema',
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ppc_company_date` (`company_id`, `created_at`),
    KEY `ppc_product_date` (`product_id`, `created_at`),
    KEY `ppc_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @c1 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_items' AND COLUMN_NAME = 'list_price');
SET @sql := IF(@c1 = 0,
    'ALTER TABLE `sale_items` ADD COLUMN `list_price` DECIMAL(12,2) NULL AFTER `unit_price`',
    'SELECT "sale_items.list_price ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Cambiar precios y costos', 'products.prices', 'products', 'Modificar el precio de venta y el costo de productos ya creados (edición, tabla de inventario, ajuste masivo, importación)', NOW(), NOW());

-- Quien hoy puede editar productos conserva la posibilidad de cambiar precios.
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT rp.role_id, p.id
FROM `role_permission` rp
JOIN `permissions` pe ON pe.id = rp.permission_id AND pe.slug = 'products.edit'
JOIN `permissions` p  ON p.slug = 'products.prices';

-- También a quien tiene 'products.edit' asignado directamente (por empresa).
INSERT IGNORE INTO `user_permission` (`user_id`, `company_id`, `permission_id`)
SELECT up.user_id, up.company_id, p.id
FROM `user_permission` up
JOIN `permissions` pe ON pe.id = up.permission_id AND pe.slug = 'products.edit'
JOIN `permissions` p  ON p.slug = 'products.prices';

-- Verificación (opcional):
-- SELECT r.name, p.slug FROM roles r JOIN role_permission rp ON rp.role_id = r.id
-- JOIN permissions p ON p.id = rp.permission_id WHERE p.slug = 'products.prices';
