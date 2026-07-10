-- =====================================================================
--  VR Motors — Fidelización Fase 2: vencimiento (lotes FIFO) + campañas
--  Equivale a la migración: 2026_06_20_000001_loyalty_phase2.php
--  Ejecutar UNA sola vez en producción. MySQL 8.0+.
-- =====================================================================

-- 1) Vencimiento configurable (meses; NULL/0 = no vence)
ALTER TABLE `loyalty_settings`
    ADD COLUMN `expiration_months` SMALLINT UNSIGNED NULL AFTER `min_purchase`;

-- 2) Lotes FIFO + fecha de expiración en el ledger + tipo 'expire'
ALTER TABLE `loyalty_point_movements`
    ADD COLUMN `points_remaining` INT NULL AFTER `points`,
    ADD COLUMN `expires_at` TIMESTAMP NULL AFTER `points_remaining`,
    MODIFY COLUMN `type` ENUM('earn','redeem','adjust','expire') NOT NULL;

-- Backfill: lotes 'earn' existentes conservan su saldo completo disponible
UPDATE `loyalty_point_movements` SET `points_remaining` = `points`
    WHERE `type` = 'earn' AND `points_remaining` IS NULL;

-- 3) Campañas (multiplicadores temporales de puntos)
CREATE TABLE IF NOT EXISTS `loyalty_campaigns` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(255)    NOT NULL,
    `multiplier` DECIMAL(5,2)    NOT NULL DEFAULT 2.00,
    `starts_at`  DATE            NOT NULL,
    `ends_at`    DATE            NOT NULL,
    `active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_loyalty_campaigns_company_active` (`company_id`, `active`),
    CONSTRAINT `loyalty_campaigns_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_campaigns_user_fk`    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
