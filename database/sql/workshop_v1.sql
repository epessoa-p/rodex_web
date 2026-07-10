-- ============================================================
--  VR Motors — Módulo Taller (Workshop) v1
--  Flujo: Recepción → Diagnóstico → OT → Mecánico → Repuestos → Pago → Entrega
--  Estados y valores ENUM en español.
-- ============================================================

-- ── 1. MECÁNICOS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mechanics` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`      BIGINT UNSIGNED NOT NULL,
    `user_id`         BIGINT UNSIGNED NULL,
    `name`            VARCHAR(255)    NOT NULL,
    `specialty`       VARCHAR(150)    NULL,
    `phone`           VARCHAR(30)     NULL,
    `commission_rate` DECIMAL(5,2)    NULL COMMENT '% comisión opcional',
    `active`          TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP NULL,
    `updated_at`      TIMESTAMP NULL,
    `deleted_at`      TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_mechanics_company` (`company_id`),
    CONSTRAINT `mechanics_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `mechanics_user_fk`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. SERVICIOS (catálogo de mano de obra) ──────────────────
CREATE TABLE IF NOT EXISTS `services` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`     BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(255)    NOT NULL,
    `description`    VARCHAR(500)    NULL,
    `price`          DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `estimated_time` VARCHAR(50)     NULL,
    `active`         TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    `deleted_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_services_company` (`company_id`),
    CONSTRAINT `services_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. ÓRDENES DE TRABAJO ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `work_orders` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `branch_id`                BIGINT UNSIGNED NULL,
    `client_id`                BIGINT UNSIGNED NOT NULL,
    `vehicle_id`               BIGINT UNSIGNED NOT NULL,
    `mechanic_id`              BIGINT UNSIGNED NULL,
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `code`                     VARCHAR(50)     NOT NULL,
    `status`                   ENUM('recibida','diagnosticada','en_proceso','terminada','entregada','anulada') NOT NULL DEFAULT 'recibida',
    -- Recepción
    `mileage`                  INT             NULL,
    `fuel_level`               VARCHAR(20)     NULL,
    `reported_issue`           TEXT            NULL,
    `received_items`           TEXT            NULL,
    `reception_date`           DATE            NOT NULL,
    -- Diagnóstico
    `diagnosis`                TEXT            NULL,
    `diagnosis_date`           DATE            NULL,
    -- Cobro
    `payment_type`             ENUM('contado','credito') NULL,
    `subtotal_services`        DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `subtotal_parts`           DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `discount`                 DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `tax`                      DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `total`                    DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `paid_amount`              DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `payment_status`           ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
    -- Entrega
    `delivered_at`             DATETIME        NULL,
    `delivered_to`             VARCHAR(255)    NULL,
    `delivery_notes`           TEXT            NULL,
    `notes`                    TEXT            NULL,
    `created_by`               BIGINT UNSIGNED NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    `deleted_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_wo_company` (`company_id`),
    KEY `idx_wo_status` (`status`),
    KEY `idx_wo_client` (`client_id`),
    CONSTRAINT `wo_company_fk`  FOREIGN KEY (`company_id`)  REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `wo_branch_fk`   FOREIGN KEY (`branch_id`)   REFERENCES `branches`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `wo_client_fk`   FOREIGN KEY (`client_id`)   REFERENCES `clients`(`id`),
    CONSTRAINT `wo_vehicle_fk`  FOREIGN KEY (`vehicle_id`)  REFERENCES `vehicles`(`id`),
    CONSTRAINT `wo_mechanic_fk` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics`(`id`) ON DELETE SET NULL,
    CONSTRAINT `wo_session_fk`  FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `wo_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `work_order_services` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `work_order_id` BIGINT UNSIGNED NOT NULL,
    `service_id`    BIGINT UNSIGNED NULL,
    `mechanic_id`   BIGINT UNSIGNED NULL,
    `description`   VARCHAR(500)    NOT NULL,
    `price`         DECIMAL(15,2)   NOT NULL,
    `quantity`      DECIMAL(15,2)   NOT NULL DEFAULT 1.00,
    `subtotal`      DECIMAL(15,2)   NOT NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_wos_order` (`work_order_id`),
    CONSTRAINT `wos_order_fk`    FOREIGN KEY (`work_order_id`) REFERENCES `work_orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `wos_service_fk`  FOREIGN KEY (`service_id`)    REFERENCES `services`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `wos_mechanic_fk` FOREIGN KEY (`mechanic_id`)   REFERENCES `mechanics`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `work_order_parts` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `work_order_id` BIGINT UNSIGNED NOT NULL,
    `product_id`    BIGINT UNSIGNED NOT NULL,
    `quantity`      DECIMAL(15,2)   NOT NULL,
    `unit_price`    DECIMAL(15,2)   NOT NULL,
    `subtotal`      DECIMAL(15,2)   NOT NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_wop_order` (`work_order_id`),
    CONSTRAINT `wop_order_fk`   FOREIGN KEY (`work_order_id`) REFERENCES `work_orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `wop_product_fk` FOREIGN KEY (`product_id`)    REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `work_order_installments` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`    BIGINT UNSIGNED NOT NULL,
    `work_order_id` BIGINT UNSIGNED NOT NULL,
    `number`        SMALLINT        NOT NULL,
    `due_date`      DATE            NOT NULL,
    `amount`        DECIMAL(15,2)   NOT NULL,
    `paid_amount`   DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `status`        ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_woi_order` (`work_order_id`),
    KEY `idx_woi_due` (`due_date`, `status`),
    CONSTRAINT `woi_company_fk` FOREIGN KEY (`company_id`)    REFERENCES `companies`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `woi_order_fk`   FOREIGN KEY (`work_order_id`) REFERENCES `work_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `work_order_payments` (
    `id`                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`                BIGINT UNSIGNED NOT NULL,
    `work_order_id`             BIGINT UNSIGNED NOT NULL,
    `work_order_installment_id` BIGINT UNSIGNED NULL,
    `cash_register_session_id`  BIGINT UNSIGNED NULL,
    `amount`                    DECIMAL(15,2)   NOT NULL,
    `payment_date`              DATE            NOT NULL,
    `method`                    VARCHAR(50)     NULL,
    `reference`                 VARCHAR(100)    NULL,
    `notes`                     VARCHAR(500)    NULL,
    `user_id`                   BIGINT UNSIGNED NOT NULL,
    `created_at`                TIMESTAMP NULL,
    `updated_at`                TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_wopay_order` (`work_order_id`),
    CONSTRAINT `wopay_company_fk`     FOREIGN KEY (`company_id`)                REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `wopay_order_fk`       FOREIGN KEY (`work_order_id`)             REFERENCES `work_orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `wopay_installment_fk` FOREIGN KEY (`work_order_installment_id`) REFERENCES `work_order_installments`(`id`) ON DELETE SET NULL,
    CONSTRAINT `wopay_session_fk`     FOREIGN KEY (`cash_register_session_id`)  REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `wopay_user_fk`        FOREIGN KEY (`user_id`)                   REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PERMISOS ─────────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Taller',           'workshop.view',    'workshop', NULL, NOW(), NOW()),
('Crear OT',             'workshop.create',  'workshop', NULL, NOW(), NOW()),
('Editar OT',            'workshop.edit',    'workshop', NULL, NOW(), NOW()),
('Eliminar OT',          'workshop.delete',  'workshop', NULL, NOW(), NOW()),
('Entregar/Cobrar OT',   'workshop.deliver', 'workshop', NULL, NOW(), NOW()),
('Ver Servicios',        'services.view',    'services', NULL, NOW(), NOW()),
('Crear Servicios',      'services.create',  'services', NULL, NOW(), NOW()),
('Editar Servicios',     'services.edit',    'services', NULL, NOW(), NOW()),
('Eliminar Servicios',   'services.delete',  'services', NULL, NOW(), NOW()),
('Ver Mecánicos',        'mechanics.view',   'mechanics', NULL, NOW(), NOW()),
('Crear Mecánicos',      'mechanics.create', 'mechanics', NULL, NOW(), NOW()),
('Editar Mecánicos',     'mechanics.edit',   'mechanics', NULL, NOW(), NOW()),
('Eliminar Mecánicos',   'mechanics.delete', 'mechanics', NULL, NOW(), NOW());

-- ── ASIGNAR A ROLES ──────────────────────────────────────────
-- Admin: todo
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module IN ('workshop','services','mechanics');

-- Gerente: todo salvo delete
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.module IN ('workshop','services','mechanics')
  AND p.slug NOT LIKE '%.delete';

-- Cajero: operar taller, ver catálogos
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('workshop.view','workshop.create','workshop.edit','workshop.deliver','services.view','mechanics.view');
