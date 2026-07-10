-- ============================================================
--  VR Motors — Ventas v2 (Fase 2): Cotizaciones y Devoluciones
-- ============================================================

-- ── 1. COTIZACIONES ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `quotes` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`        BIGINT UNSIGNED NOT NULL,
    `branch_id`         BIGINT UNSIGNED NULL,
    `client_id`         BIGINT UNSIGNED NULL,
    `code`              VARCHAR(50)     NOT NULL,
    `status`            ENUM('draft','sent','accepted','rejected','expired','converted') NOT NULL DEFAULT 'draft',
    `quote_date`        DATE            NOT NULL,
    `valid_until`       DATE            NULL,
    `subtotal`          DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `discount`          DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `tax`               DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `total`             DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `notes`             TEXT            NULL,
    `converted_sale_id` BIGINT UNSIGNED NULL,
    `created_by`        BIGINT UNSIGNED NOT NULL,
    `created_at`        TIMESTAMP NULL,
    `updated_at`        TIMESTAMP NULL,
    `deleted_at`        TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_quotes_company` (`company_id`),
    KEY `idx_quotes_client` (`client_id`),
    KEY `idx_quotes_status` (`status`),
    CONSTRAINT `quotes_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `quotes_branch_fk`  FOREIGN KEY (`branch_id`)  REFERENCES `branches`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `quotes_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE SET NULL,
    CONSTRAINT `quotes_sale_fk`    FOREIGN KEY (`converted_sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL,
    CONSTRAINT `quotes_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `quote_id`   BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity`   DECIMAL(15,2)   NOT NULL,
    `unit_price` DECIMAL(15,2)   NOT NULL,
    `discount`   DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `subtotal`   DECIMAL(15,2)   NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_quote_items_quote` (`quote_id`),
    CONSTRAINT `quote_items_quote_fk`   FOREIGN KEY (`quote_id`)   REFERENCES `quotes`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `quote_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. DEVOLUCIONES ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sale_returns` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `sale_id`                  BIGINT UNSIGNED NOT NULL,
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `code`                     VARCHAR(50)     NOT NULL,
    `return_date`              DATE            NOT NULL,
    `refund_method`            ENUM('cash','credit_note') NOT NULL DEFAULT 'cash',
    `reason`                   VARCHAR(255)    NULL,
    `total`                    DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `notes`                    TEXT            NULL,
    `created_by`               BIGINT UNSIGNED NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    `deleted_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sale_returns_company` (`company_id`),
    KEY `idx_sale_returns_sale` (`sale_id`),
    CONSTRAINT `sale_returns_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sale_returns_sale_fk`    FOREIGN KEY (`sale_id`)    REFERENCES `sales`(`id`),
    CONSTRAINT `sale_returns_session_fk` FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `sale_returns_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sale_return_items` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sale_return_id` BIGINT UNSIGNED NOT NULL,
    `sale_item_id`   BIGINT UNSIGNED NULL,
    `product_id`     BIGINT UNSIGNED NOT NULL,
    `quantity`       DECIMAL(15,2)   NOT NULL,
    `unit_price`     DECIMAL(15,2)   NOT NULL,
    `subtotal`       DECIMAL(15,2)   NOT NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sale_return_items_return` (`sale_return_id`),
    CONSTRAINT `sale_return_items_return_fk`  FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sale_return_items_item_fk`    FOREIGN KEY (`sale_item_id`)   REFERENCES `sale_items`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `sale_return_items_product_fk` FOREIGN KEY (`product_id`)     REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. PERMISOS ──────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Cotizaciones',      'quotes.view',   'quotes', NULL, NOW(), NOW()),
('Crear Cotizaciones',    'quotes.create', 'quotes', NULL, NOW(), NOW()),
('Editar Cotizaciones',   'quotes.edit',   'quotes', NULL, NOW(), NOW()),
('Eliminar Cotizaciones', 'quotes.delete', 'quotes', NULL, NOW(), NOW()),
('Ver Devoluciones',      'sale-returns.view',   'sale_returns', NULL, NOW(), NOW()),
('Crear Devoluciones',    'sale-returns.create', 'sale_returns', NULL, NOW(), NOW());

-- ── 4. ASIGNAR A ROLES ───────────────────────────────────────
-- Admin: todo
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module IN ('quotes','sale_returns');

-- Gerente
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN ('quotes.view','quotes.create','quotes.edit','sale-returns.view','sale-returns.create');

-- Cajero
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('quotes.view','quotes.create','sale-returns.view','sale-returns.create');
