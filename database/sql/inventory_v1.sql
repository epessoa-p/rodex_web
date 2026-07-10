-- ============================================================
--  VR Motors — Inventario Real v1
--  Ejecutar en orden
-- ============================================================

CREATE TABLE IF NOT EXISTS `product_categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `name`        VARCHAR(255)    NOT NULL,
    `description` VARCHAR(500)    NULL,
    `active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pcat_company` (`company_id`),
    CONSTRAINT `pcat_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_brands` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `name`        VARCHAR(255)    NOT NULL,
    `description` VARCHAR(500)    NULL,
    `active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pbrand_company` (`company_id`),
    CONSTRAINT `pbrand_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_photos` (
    `id`         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED  NOT NULL,
    `company_id` BIGINT UNSIGNED  NOT NULL,
    `file_path`  VARCHAR(500)     NOT NULL,
    `file_name`  VARCHAR(255)     NULL,
    `is_main`    TINYINT(1)       NOT NULL DEFAULT 0,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pphoto_product` (`product_id`),
    CONSTRAINT `pphoto_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alter products table
ALTER TABLE `products`
    DROP FOREIGN KEY IF EXISTS `products_credit_category_id_foreign`,
    DROP COLUMN IF EXISTS `credit_category_id`,
    DROP COLUMN IF EXISTS `category`,
    ADD COLUMN `category_id`       BIGINT UNSIGNED NULL AFTER `company_id`,
    ADD COLUMN `brand_id`          BIGINT UNSIGNED NULL AFTER `category_id`,
    ADD COLUMN `barcode`           VARCHAR(100)    NULL AFTER `sku`,
    ADD COLUMN `compatible_models` TEXT            NULL AFTER `description`;

ALTER TABLE `products`
    ADD CONSTRAINT `products_category_fk` FOREIGN KEY (`category_id`) REFERENCES `product_categories`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `products_brand_fk`    FOREIGN KEY (`brand_id`)    REFERENCES `product_brands`(`id`)    ON DELETE SET NULL;

-- Alter inventory_movements
ALTER TABLE `inventory_movements`
    MODIFY COLUMN `type` ENUM('in','out','transfer','adjustment') NOT NULL,
    ADD COLUMN `destination_warehouse_id` BIGINT UNSIGNED NULL AFTER `warehouse_id`,
    ADD COLUMN `adjustment_reason`        VARCHAR(255)    NULL;

ALTER TABLE `inventory_movements`
    ADD CONSTRAINT `inv_mov_dest_wh_fk` FOREIGN KEY (`destination_warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE SET NULL;

-- Permissions
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Categorías',      'product-categories.view',   'product_categories', NULL, NOW(), NOW()),
('Crear Categorías',    'product-categories.create', 'product_categories', NULL, NOW(), NOW()),
('Editar Categorías',   'product-categories.edit',   'product_categories', NULL, NOW(), NOW()),
('Eliminar Categorías', 'product-categories.delete', 'product_categories', NULL, NOW(), NOW()),
('Ver Marcas',          'product-brands.view',       'product_brands',     NULL, NOW(), NOW()),
('Crear Marcas',        'product-brands.create',     'product_brands',     NULL, NOW(), NOW()),
('Editar Marcas',       'product-brands.edit',       'product_brands',     NULL, NOW(), NOW()),
('Eliminar Marcas',     'product-brands.delete',     'product_brands',     NULL, NOW(), NOW());

-- Role permissions: admin gets all new permissions
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN ('product-categories.view','product-categories.create','product-categories.edit','product-categories.delete',
                 'product-brands.view','product-brands.create','product-brands.edit','product-brands.delete');

-- Manager: view + create + edit
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN ('product-categories.view','product-categories.create','product-categories.edit',
                 'product-brands.view','product-brands.create','product-brands.edit');

-- Cashier + Employee: view only
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('cashier','employee')
  AND p.slug IN ('product-categories.view','product-brands.view');
