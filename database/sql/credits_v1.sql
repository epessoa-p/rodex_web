-- ============================================================
--  Rodex — Expansión CRÉDITOS v1
--  Planes de Pago (con interés) + Solicitudes + ampliación de sales
-- ============================================================

-- ── 1. PLANES DE PAGO (plantillas) ───────────────────────────
CREATE TABLE IF NOT EXISTS `payment_plans` (
    `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`             BIGINT UNSIGNED NOT NULL,
    `name`                   VARCHAR(255)    NOT NULL,
    `number_of_installments` SMALLINT        NOT NULL DEFAULT 1,
    `frequency_days`         SMALLINT        NOT NULL DEFAULT 30,
    `interest_rate`          DECIMAL(5,2)    NOT NULL DEFAULT 0.00 COMMENT '% interés sobre saldo financiado',
    `active`                 TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`             TIMESTAMP NULL,
    `updated_at`             TIMESTAMP NULL,
    `deleted_at`             TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payment_plans_company` (`company_id`),
    CONSTRAINT `payment_plans_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. SOLICITUDES DE CRÉDITO ────────────────────────────────
CREATE TABLE IF NOT EXISTS `credit_applications` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`          BIGINT UNSIGNED NOT NULL,
    `client_id`           BIGINT UNSIGNED NOT NULL,
    `code`                VARCHAR(50)     NOT NULL,
    `requested_amount`    DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `down_payment`        DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `installments_count`  SMALLINT        NULL,
    `frequency_days`      SMALLINT        NULL,
    `payment_plan_id`     BIGINT UNSIGNED NULL,
    `guarantor_name`      VARCHAR(255)    NULL,
    `guarantor_phone`     VARCHAR(30)     NULL,
    `guarantor_notes`     TEXT            NULL,
    `notes`               TEXT            NULL,
    `status`              ENUM('pendiente','aprobada','rechazada','convertida') NOT NULL DEFAULT 'pendiente',
    `approved_amount`     DECIMAL(15,2)   NULL,
    `evaluation_notes`    TEXT            NULL,
    `evaluated_by`        BIGINT UNSIGNED NULL,
    `converted_sale_id`   BIGINT UNSIGNED NULL,
    `created_by`          BIGINT UNSIGNED NOT NULL,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    `deleted_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_credit_apps_company` (`company_id`),
    KEY `idx_credit_apps_status` (`status`),
    CONSTRAINT `credit_apps_company_fk`  FOREIGN KEY (`company_id`)       REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `credit_apps_client_fk`   FOREIGN KEY (`client_id`)        REFERENCES `clients`(`id`),
    CONSTRAINT `credit_apps_plan_fk`     FOREIGN KEY (`payment_plan_id`)  REFERENCES `payment_plans`(`id`) ON DELETE SET NULL,
    CONSTRAINT `credit_apps_eval_fk`     FOREIGN KEY (`evaluated_by`)     REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `credit_apps_sale_fk`     FOREIGN KEY (`converted_sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL,
    CONSTRAINT `credit_apps_creator_fk`  FOREIGN KEY (`created_by`)       REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. AMPLIAR sales ─────────────────────────────────────────
ALTER TABLE `sales`
    ADD COLUMN `interest`              DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `tax`,
    ADD COLUMN `sale_category`         ENUM('producto','moto') NOT NULL DEFAULT 'producto' AFTER `sale_type`,
    ADD COLUMN `moto_unit_id`          BIGINT UNSIGNED NULL AFTER `client_id`,
    ADD COLUMN `payment_plan_id`       BIGINT UNSIGNED NULL AFTER `sale_category`,
    ADD COLUMN `credit_application_id` BIGINT UNSIGNED NULL AFTER `payment_plan_id`;

ALTER TABLE `sales`
    ADD CONSTRAINT `sales_payment_plan_fk` FOREIGN KEY (`payment_plan_id`)       REFERENCES `payment_plans`(`id`)       ON DELETE SET NULL,
    ADD CONSTRAINT `sales_credit_app_fk`   FOREIGN KEY (`credit_application_id`) REFERENCES `credit_applications`(`id`) ON DELETE SET NULL;
-- (FK de moto_unit_id se agrega en motos_v1.sql)

-- ── 4. PERMISOS ──────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Solicitudes',      'credit-applications.view',    'credit_applications', NULL, NOW(), NOW()),
('Crear Solicitudes',    'credit-applications.create',  'credit_applications', NULL, NOW(), NOW()),
('Editar Solicitudes',   'credit-applications.edit',    'credit_applications', NULL, NOW(), NOW()),
('Aprobar Solicitudes',  'credit-applications.approve', 'credit_applications', NULL, NOW(), NOW()),
('Ver Planes de Pago',   'payment-plans.view',   'payment_plans', NULL, NOW(), NOW()),
('Crear Planes de Pago', 'payment-plans.create', 'payment_plans', NULL, NOW(), NOW()),
('Editar Planes de Pago','payment-plans.edit',   'payment_plans', NULL, NOW(), NOW()),
('Eliminar Planes de Pago','payment-plans.delete','payment_plans', NULL, NOW(), NOW()),
('Ver Reportes de Crédito','credit-reports.view', 'credit_reports', NULL, NOW(), NOW());

-- Admin: todo
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module IN ('credit_applications','payment_plans','credit_reports');

-- Gerente: ver/crear/editar/aprobar (sin eliminar planes)
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN (
    'credit-applications.view','credit-applications.create','credit-applications.edit','credit-applications.approve',
    'payment-plans.view','payment-plans.create','payment-plans.edit',
    'credit-reports.view'
  );

-- Cajero: ver
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('credit-applications.view','credit-applications.create','payment-plans.view','credit-reports.view');
