-- ============================================================
--  Rodex — Pago a mecánicos (mechanic_payments)
--  Liquidación de comisiones: se paga al mecánico (desde caja o
--  desde una cuenta de tesorería) contra lo ganado por su mano de obra
--  en las OTs entregadas. Guarda cada pago para llevar el pendiente.
--
--  Ejecutar UNA sola vez en producción. Idempotente. MySQL 8.0+.
-- ============================================================

-- ── 1. PAGOS A MECÁNICOS ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mechanic_payments` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `mechanic_id`              BIGINT UNSIGNED NOT NULL,
    `amount`                   DECIMAL(15,2)   NOT NULL,
    `payment_source`           ENUM('cash','treasury') NOT NULL DEFAULT 'cash',
    `treasury_account_id`      BIGINT UNSIGNED NULL,
    `cash_register_session_id` BIGINT UNSIGNED NULL,
    `method`                   VARCHAR(30)     NULL,
    `notes`                    VARCHAR(1000)   NULL,
    `payment_date`             DATE            NOT NULL,
    `created_by`               BIGINT UNSIGNED NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_mech_pay_company` (`company_id`),
    KEY `idx_mech_pay_mechanic` (`mechanic_id`),
    CONSTRAINT `mech_pay_company_fk`  FOREIGN KEY (`company_id`)               REFERENCES `companies`(`id`)                ON DELETE CASCADE,
    CONSTRAINT `mech_pay_mechanic_fk` FOREIGN KEY (`mechanic_id`)              REFERENCES `mechanics`(`id`)                ON DELETE CASCADE,
    CONSTRAINT `mech_pay_account_fk`  FOREIGN KEY (`treasury_account_id`)      REFERENCES `treasury_accounts`(`id`)        ON DELETE SET NULL,
    CONSTRAINT `mech_pay_session_fk`  FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`)   ON DELETE SET NULL,
    CONSTRAINT `mech_pay_user_fk`     FOREIGN KEY (`created_by`)               REFERENCES `users`(`id`)                    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. PERMISOS ─────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Pago a Mecánicos',   'mechanic-payments.view', 'mechanics', NULL, NOW(), NOW()),
('Pagar a Mecánicos',      'mechanic-payments.pay',  'mechanics', NULL, NOW(), NOW());

-- ── 3. ASIGNAR A ROLES ──────────────────────────────────────
-- Admin y Gerente: ver + pagar
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug IN ('mechanic-payments.view', 'mechanic-payments.pay');
