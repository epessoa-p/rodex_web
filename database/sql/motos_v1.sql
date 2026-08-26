-- ============================================================
--  Rodex — Módulo MOTOS v1
--  Marcas, Modelos, Inventario (unidades serializadas),
--  Ventas (reusan Sale), Entregas, Garantías
-- ============================================================

-- ── 1. MARCAS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `moto_brands` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(255)    NOT NULL,
    `country`    VARCHAR(100)    NULL,
    `active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_moto_brands_company` (`company_id`),
    CONSTRAINT `moto_brands_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. MODELOS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `moto_models` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`      BIGINT UNSIGNED NOT NULL,
    `moto_brand_id`   BIGINT UNSIGNED NOT NULL,
    `name`            VARCHAR(255)    NOT NULL,
    `engine_cc`       VARCHAR(30)     NULL,
    `year`            SMALLINT        NULL,
    `suggested_price` DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `description`     TEXT            NULL,
    `active`          TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP NULL,
    `updated_at`      TIMESTAMP NULL,
    `deleted_at`      TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_moto_models_company` (`company_id`),
    KEY `idx_moto_models_brand` (`moto_brand_id`),
    CONSTRAINT `moto_models_company_fk` FOREIGN KEY (`company_id`)    REFERENCES `companies`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `moto_models_brand_fk`   FOREIGN KEY (`moto_brand_id`) REFERENCES `moto_brands`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. INVENTARIO DE MOTOS (unidades serializadas) ───────────
CREATE TABLE IF NOT EXISTS `moto_units` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`     BIGINT UNSIGNED NOT NULL,
    `moto_model_id`  BIGINT UNSIGNED NOT NULL,
    `branch_id`      BIGINT UNSIGNED NULL,
    `chassis_number` VARCHAR(80)     NOT NULL COMMENT 'VIN / Chasis',
    `engine_number`  VARCHAR(80)     NULL,
    `color`          VARCHAR(50)     NULL,
    `year`           SMALLINT        NULL,
    `cost`           DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `price`          DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `status`         ENUM('disponible','reservada','vendida','entregada','anulada') NOT NULL DEFAULT 'disponible',
    `sale_id`        BIGINT UNSIGNED NULL,
    `delivered_at`   DATETIME        NULL,
    `delivered_to`   VARCHAR(255)    NULL,
    `assigned_plate` VARCHAR(20)     NULL,
    `delivery_notes` TEXT            NULL,
    `notes`          TEXT            NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    `deleted_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_moto_units_chassis` (`company_id`, `chassis_number`),
    KEY `idx_moto_units_model` (`moto_model_id`),
    KEY `idx_moto_units_status` (`status`),
    CONSTRAINT `moto_units_company_fk` FOREIGN KEY (`company_id`)   REFERENCES `companies`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `moto_units_model_fk`   FOREIGN KEY (`moto_model_id`) REFERENCES `moto_models`(`id`),
    CONSTRAINT `moto_units_branch_fk`  FOREIGN KEY (`branch_id`)     REFERENCES `branches`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `moto_units_sale_fk`    FOREIGN KEY (`sale_id`)       REFERENCES `sales`(`id`)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK diferida desde sales hacia la unidad de moto
ALTER TABLE `sales`
    ADD CONSTRAINT `sales_moto_unit_fk` FOREIGN KEY (`moto_unit_id`) REFERENCES `moto_units`(`id`) ON DELETE SET NULL;

-- ── 4. GARANTÍAS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `warranties` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   BIGINT UNSIGNED NOT NULL,
    `moto_unit_id` BIGINT UNSIGNED NOT NULL,
    `sale_id`      BIGINT UNSIGNED NULL,
    `client_id`    BIGINT UNSIGNED NULL,
    `code`         VARCHAR(50)     NOT NULL,
    `start_date`   DATE            NOT NULL,
    `months`       SMALLINT        NOT NULL DEFAULT 12,
    `coverage`     TEXT            NULL,
    `status`       ENUM('vigente','vencida','anulada') NOT NULL DEFAULT 'vigente',
    `notes`        TEXT            NULL,
    `created_by`   BIGINT UNSIGNED NOT NULL,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    `deleted_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_warranties_company` (`company_id`),
    KEY `idx_warranties_unit` (`moto_unit_id`),
    CONSTRAINT `warranties_company_fk` FOREIGN KEY (`company_id`)   REFERENCES `companies`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `warranties_unit_fk`    FOREIGN KEY (`moto_unit_id`) REFERENCES `moto_units`(`id`),
    CONSTRAINT `warranties_sale_fk`    FOREIGN KEY (`sale_id`)      REFERENCES `sales`(`id`)       ON DELETE SET NULL,
    CONSTRAINT `warranties_client_fk`  FOREIGN KEY (`client_id`)    REFERENCES `clients`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `warranties_creator_fk` FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. PERMISOS ──────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Marcas Moto',     'moto-brands.view',   'moto_brands', NULL, NOW(), NOW()),
('Crear Marcas Moto',   'moto-brands.create', 'moto_brands', NULL, NOW(), NOW()),
('Editar Marcas Moto',  'moto-brands.edit',   'moto_brands', NULL, NOW(), NOW()),
('Eliminar Marcas Moto','moto-brands.delete', 'moto_brands', NULL, NOW(), NOW()),
('Ver Modelos Moto',    'moto-models.view',   'moto_models', NULL, NOW(), NOW()),
('Crear Modelos Moto',  'moto-models.create', 'moto_models', NULL, NOW(), NOW()),
('Editar Modelos Moto', 'moto-models.edit',   'moto_models', NULL, NOW(), NOW()),
('Eliminar Modelos Moto','moto-models.delete','moto_models', NULL, NOW(), NOW()),
('Ver Inventario Motos','moto-units.view',    'moto_units', NULL, NOW(), NOW()),
('Crear Unidades Moto', 'moto-units.create',  'moto_units', NULL, NOW(), NOW()),
('Editar Unidades Moto','moto-units.edit',    'moto_units', NULL, NOW(), NOW()),
('Eliminar Unidades Moto','moto-units.delete','moto_units', NULL, NOW(), NOW()),
('Ver Ventas Moto',     'moto-sales.view',    'moto_sales', NULL, NOW(), NOW()),
('Crear Ventas Moto',   'moto-sales.create',  'moto_sales', NULL, NOW(), NOW()),
('Ver Entregas Moto',   'moto-deliveries.view','moto_deliveries', NULL, NOW(), NOW()),
('Gestionar Entregas Moto','moto-deliveries.manage','moto_deliveries', NULL, NOW(), NOW()),
('Ver Garantías',       'warranties.view',    'warranties', NULL, NOW(), NOW()),
('Gestionar Garantías', 'warranties.manage',  'warranties', NULL, NOW(), NOW());

-- ── 6. ASIGNAR A ROLES ───────────────────────────────────────
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module IN ('moto_brands','moto_models','moto_units','moto_sales','moto_deliveries','warranties');

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.module IN ('moto_brands','moto_models','moto_units','moto_sales','moto_deliveries','warranties')
  AND p.slug NOT LIKE '%.delete';

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('moto-brands.view','moto-models.view','moto-units.view','moto-sales.view','moto-sales.create','moto-deliveries.view','moto-deliveries.manage','warranties.view','warranties.manage');
