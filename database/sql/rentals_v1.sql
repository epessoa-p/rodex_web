-- ============================================================
--  VR Motors — Módulo ALQUILERES de motos (Fase 1)
-- ============================================================

-- ── Ajustes a tablas existentes ──────────────────────────────
ALTER TABLE `moto_models`
    ADD COLUMN `daily_rate` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `suggested_price`;

ALTER TABLE `moto_units`
    MODIFY COLUMN `status` ENUM('disponible','reservada','alquilada','mantenimiento','vendida','entregada','anulada') NOT NULL DEFAULT 'disponible';

ALTER TABLE `work_orders`
    ADD COLUMN `moto_unit_id` BIGINT UNSIGNED NULL AFTER `vehicle_id`,
    MODIFY COLUMN `vehicle_id` BIGINT UNSIGNED NULL,
    MODIFY COLUMN `client_id`  BIGINT UNSIGNED NULL;

ALTER TABLE `work_orders`
    ADD CONSTRAINT `wo_moto_unit_fk` FOREIGN KEY (`moto_unit_id`) REFERENCES `moto_units`(`id`) ON DELETE SET NULL;

-- ── 1. CONTRATOS DE ALQUILER ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `rental_contracts` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `branch_id`                BIGINT UNSIGNED NULL,
    `client_id`                BIGINT UNSIGNED NOT NULL,
    `moto_unit_id`             BIGINT UNSIGNED NOT NULL,
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `code`                     VARCHAR(50)     NOT NULL,
    `status`                   ENUM('reservada','contrato','entregada','devuelta','cerrada','anulada') NOT NULL DEFAULT 'reservada',
    `start_date`               DATE            NOT NULL,
    `end_date`                 DATE            NOT NULL,
    `days`                     INT             NOT NULL DEFAULT 1,
    `daily_rate`               DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `rental_total`             DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `deposit`                  DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `penalties_total`          DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `total`                    DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `paid_amount`              DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `payment_status`           ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
    `deposit_status`           ENUM('retenido','devuelto','parcial','aplicado') NOT NULL DEFAULT 'retenido',
    `deposit_refunded`         DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    -- Inspección de salida
    `delivered_at`             DATETIME        NULL,
    `delivery_mileage`         INT             NULL,
    `delivery_fuel`            VARCHAR(20)     NULL,
    `delivery_notes`           TEXT            NULL,
    -- Inspección de entrada
    `returned_at`              DATETIME        NULL,
    `return_mileage`           INT             NULL,
    `return_fuel`              VARCHAR(20)     NULL,
    `return_notes`             TEXT            NULL,
    `work_order_id`            BIGINT UNSIGNED NULL,
    `notes`                    TEXT            NULL,
    `created_by`               BIGINT UNSIGNED NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    `deleted_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rentals_company` (`company_id`),
    KEY `idx_rentals_status` (`status`),
    KEY `idx_rentals_unit_dates` (`moto_unit_id`, `start_date`, `end_date`),
    CONSTRAINT `rentals_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rentals_branch_fk`  FOREIGN KEY (`branch_id`)  REFERENCES `branches`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `rentals_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`),
    CONSTRAINT `rentals_unit_fk`    FOREIGN KEY (`moto_unit_id`) REFERENCES `moto_units`(`id`),
    CONSTRAINT `rentals_session_fk` FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `rentals_wo_fk`      FOREIGN KEY (`work_order_id`) REFERENCES `work_orders`(`id`) ON DELETE SET NULL,
    CONSTRAINT `rentals_creator_fk` FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. PAGOS DE ALQUILER ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rental_payments` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `rental_contract_id`       BIGINT UNSIGNED NOT NULL,
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `type`                     ENUM('alquiler','deposito','penalizacion','devolucion_deposito') NOT NULL DEFAULT 'alquiler',
    `amount`                   DECIMAL(15,2)   NOT NULL,
    `method`                   VARCHAR(50)     NULL,
    `payment_date`             DATE            NOT NULL,
    `reference`                VARCHAR(100)    NULL,
    `notes`                    VARCHAR(500)    NULL,
    `user_id`                  BIGINT UNSIGNED NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rental_payments_contract` (`rental_contract_id`),
    CONSTRAINT `rental_payments_company_fk`  FOREIGN KEY (`company_id`)         REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_payments_contract_fk` FOREIGN KEY (`rental_contract_id`) REFERENCES `rental_contracts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_payments_session_fk`  FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `rental_payments_user_fk`     FOREIGN KEY (`user_id`)            REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. PENALIZACIONES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rental_penalties` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`         BIGINT UNSIGNED NOT NULL,
    `rental_contract_id` BIGINT UNSIGNED NOT NULL,
    `concept`            VARCHAR(255)    NOT NULL,
    `amount`             DECIMAL(15,2)   NOT NULL,
    `penalty_date`       DATE            NOT NULL,
    `notes`              VARCHAR(500)    NULL,
    `created_by`         BIGINT UNSIGNED NOT NULL,
    `created_at`         TIMESTAMP NULL,
    `updated_at`         TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rental_penalties_contract` (`rental_contract_id`),
    CONSTRAINT `rental_penalties_company_fk`  FOREIGN KEY (`company_id`)         REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_penalties_contract_fk` FOREIGN KEY (`rental_contract_id`) REFERENCES `rental_contracts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_penalties_creator_fk`  FOREIGN KEY (`created_by`)         REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. PERMISOS ──────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Alquileres',     'rentals.view',    'rentals', NULL, NOW(), NOW()),
('Crear Alquileres',   'rentals.create',  'rentals', NULL, NOW(), NOW()),
('Editar Alquileres',  'rentals.edit',    'rentals', NULL, NOW(), NOW()),
('Eliminar Alquileres','rentals.delete',  'rentals', NULL, NOW(), NOW()),
('Entregar Alquiler',  'rentals.deliver', 'rentals', NULL, NOW(), NOW()),
('Devolver Alquiler',  'rentals.return',  'rentals', NULL, NOW(), NOW()),
('Cobrar Alquiler',    'rentals.pay',     'rentals', NULL, NOW(), NOW());

-- ── 5. ASIGNAR A ROLES ───────────────────────────────────────
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin' AND p.module = 'rentals';

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager' AND p.module = 'rentals' AND p.slug <> 'rentals.delete';

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('rentals.view','rentals.create','rentals.deliver','rentals.return','rentals.pay');
