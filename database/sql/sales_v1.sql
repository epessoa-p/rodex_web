-- ============================================================
--  Rodex — Módulo de Ventas v1 (Fase 1 núcleo)
--  POS, Ventas contado/crédito, Cuotas, Cobranza, Vehículos
-- ============================================================

-- ── 1. VEHÍCULOS (base para taller futuro) ───────────────────
CREATE TABLE IF NOT EXISTS `vehicles` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `client_id`  BIGINT UNSIGNED NOT NULL,
    `brand`      VARCHAR(100)    NOT NULL,
    `model`      VARCHAR(100)    NULL,
    `engine_cc`  VARCHAR(30)     NULL COMMENT 'Cilindrada',
    `year`       SMALLINT        NULL,
    `plate`      VARCHAR(20)     NULL COMMENT 'Placa',
    `color`      VARCHAR(40)     NULL,
    `vin`        VARCHAR(60)     NULL COMMENT 'Chasis / VIN',
    `notes`      TEXT            NULL,
    `active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vehicles_company` (`company_id`),
    KEY `idx_vehicles_client` (`client_id`),
    CONSTRAINT `vehicles_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `vehicles_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. VENTAS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sales` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `branch_id`                BIGINT UNSIGNED NULL,
    `client_id`                BIGINT UNSIGNED NULL COMMENT 'Null = cliente general',
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `code`                     VARCHAR(50)     NOT NULL,
    `sale_type`                ENUM('cash','credit') NOT NULL DEFAULT 'cash',
    `sale_date`                DATE            NOT NULL,
    `subtotal`                 DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `discount`                 DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `tax`                      DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `total`                    DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `paid_amount`              DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `payment_status`           ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
    `status`                   ENUM('completed','cancelled') NOT NULL DEFAULT 'completed',
    `notes`                    TEXT            NULL,
    `created_by`               BIGINT UNSIGNED NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    `deleted_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sales_company` (`company_id`),
    KEY `idx_sales_client` (`client_id`),
    KEY `idx_sales_paystatus` (`payment_status`),
    CONSTRAINT `sales_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sales_branch_fk`  FOREIGN KEY (`branch_id`)  REFERENCES `branches`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `sales_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE SET NULL,
    CONSTRAINT `sales_session_fk` FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `sales_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sale_items` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sale_id`    BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity`   DECIMAL(15,2)   NOT NULL,
    `unit_price` DECIMAL(15,2)   NOT NULL,
    `discount`   DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `subtotal`   DECIMAL(15,2)   NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sale_items_sale` (`sale_id`),
    CONSTRAINT `sale_items_sale_fk`    FOREIGN KEY (`sale_id`)    REFERENCES `sales`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `sale_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. CUOTAS (cronograma de crédito) ────────────────────────
CREATE TABLE IF NOT EXISTS `sale_installments` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `sale_id`     BIGINT UNSIGNED NOT NULL,
    `number`      SMALLINT        NOT NULL,
    `due_date`    DATE            NOT NULL,
    `amount`      DECIMAL(15,2)   NOT NULL,
    `paid_amount` DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `status`      ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_installments_sale` (`sale_id`),
    KEY `idx_installments_due` (`due_date`, `status`),
    CONSTRAINT `installments_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `installments_sale_fk`    FOREIGN KEY (`sale_id`)    REFERENCES `sales`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. PAGOS / COBROS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sale_payments` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `sale_id`                  BIGINT UNSIGNED NOT NULL,
    `sale_installment_id`      BIGINT UNSIGNED NULL,
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `amount`                   DECIMAL(15,2)   NOT NULL,
    `payment_date`             DATE            NOT NULL,
    `method`                   VARCHAR(50)     NULL,
    `reference`                VARCHAR(100)    NULL,
    `notes`                    VARCHAR(500)    NULL,
    `user_id`                  BIGINT UNSIGNED NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sale_payments_sale` (`sale_id`),
    CONSTRAINT `sale_payments_company_fk`     FOREIGN KEY (`company_id`)          REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sale_payments_sale_fk`        FOREIGN KEY (`sale_id`)             REFERENCES `sales`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sale_payments_installment_fk` FOREIGN KEY (`sale_installment_id`) REFERENCES `sale_installments`(`id`) ON DELETE SET NULL,
    CONSTRAINT `sale_payments_session_fk`     FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `sale_payments_user_fk`        FOREIGN KEY (`user_id`)             REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. PERMISOS ──────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Acceso al POS',     'pos.access',     'sales', NULL, NOW(), NOW()),
('Ver Ventas',        'sales.view',     'sales', NULL, NOW(), NOW()),
('Crear Ventas',      'sales.create',   'sales', NULL, NOW(), NOW()),
('Eliminar Ventas',   'sales.delete',   'sales', NULL, NOW(), NOW()),
('Ver Créditos',      'credit.view',    'credit', NULL, NOW(), NOW()),
('Cobrar Créditos',   'credit.collect', 'credit', NULL, NOW(), NOW()),
('Ver Vehículos',     'vehicles.view',   'vehicles', NULL, NOW(), NOW()),
('Crear Vehículos',   'vehicles.create', 'vehicles', NULL, NOW(), NOW()),
('Editar Vehículos',  'vehicles.edit',   'vehicles', NULL, NOW(), NOW()),
('Eliminar Vehículos','vehicles.delete', 'vehicles', NULL, NOW(), NOW());

-- ── 6. ASIGNAR A ROLES ───────────────────────────────────────
-- Admin: todo
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module IN ('sales','credit','vehicles');

-- Gerente: ver/crear/cobrar (sin eliminar)
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN (
    'pos.access','sales.view','sales.create',
    'credit.view','credit.collect',
    'vehicles.view','vehicles.create','vehicles.edit'
  );

-- Cajero: POS, ventas, cobranza, ver vehículos
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN (
    'pos.access','sales.view','sales.create',
    'credit.view','credit.collect',
    'vehicles.view'
  );
