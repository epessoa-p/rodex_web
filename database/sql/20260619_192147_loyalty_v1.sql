-- =====================================================================
--  Rodex — Módulo de Fidelización (puntos y recompensas) — v1
--  Equivale a la migración: 2026_06_19_000001_create_loyalty_tables.php
--
--  Crea: loyalty_settings, loyalty_rewards, loyalty_redemptions,
--        loyalty_point_movements  + columna clients.points_balance.
--  Ejecutar UNA sola vez en producción. MySQL 8.0+.
-- =====================================================================

-- 1) Configuración + reglas de acumulación (1 fila por empresa)
CREATE TABLE IF NOT EXISTS `loyalty_settings` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   BIGINT UNSIGNED NOT NULL,
    `enabled`      TINYINT(1)      NOT NULL DEFAULT 0,
    `earn_amount`  DECIMAL(15,2)   NOT NULL DEFAULT 20.00,
    `earn_points`  INT UNSIGNED    NOT NULL DEFAULT 100,
    `rounding`     ENUM('down','nearest','up') NOT NULL DEFAULT 'down',
    `min_purchase` DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `points_label` VARCHAR(255)    NOT NULL DEFAULT 'puntos',
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `loyalty_settings_company_unique` (`company_id`),
    CONSTRAINT `loyalty_settings_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Catálogo de recompensas
CREATE TABLE IF NOT EXISTS `loyalty_rewards` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `name`        VARCHAR(255)    NOT NULL,
    `description` TEXT            NULL,
    `image`       VARCHAR(255)    NULL,
    `points_cost` INT UNSIGNED    NOT NULL,
    `product_id`  BIGINT UNSIGNED NULL,
    `stock`       INT             NULL,
    `active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_by`  BIGINT UNSIGNED NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_loyalty_rewards_company` (`company_id`),
    CONSTRAINT `loyalty_rewards_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_rewards_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    CONSTRAINT `loyalty_rewards_user_fk`    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Canjes
CREATE TABLE IF NOT EXISTS `loyalty_redemptions` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   BIGINT UNSIGNED NOT NULL,
    `client_id`    BIGINT UNSIGNED NOT NULL,
    `reward_id`    BIGINT UNSIGNED NOT NULL,
    `points_spent` INT UNSIGNED    NOT NULL,
    `sale_id`      BIGINT UNSIGNED NULL,
    `status`       VARCHAR(255)    NOT NULL DEFAULT 'completed',
    `user_id`      BIGINT UNSIGNED NULL,
    `redeemed_at`  TIMESTAMP NULL,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_loyalty_redemptions_company_client` (`company_id`, `client_id`),
    CONSTRAINT `loyalty_redemptions_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)        ON DELETE CASCADE,
    CONSTRAINT `loyalty_redemptions_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)          ON DELETE CASCADE,
    CONSTRAINT `loyalty_redemptions_reward_fk`  FOREIGN KEY (`reward_id`)  REFERENCES `loyalty_rewards`(`id`),
    CONSTRAINT `loyalty_redemptions_sale_fk`    FOREIGN KEY (`sale_id`)    REFERENCES `sales`(`id`)            ON DELETE SET NULL,
    CONSTRAINT `loyalty_redemptions_user_fk`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Ledger de movimientos de puntos (fuente de verdad del saldo)
CREATE TABLE IF NOT EXISTS `loyalty_point_movements` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `client_id`   BIGINT UNSIGNED NOT NULL,
    `type`        ENUM('earn','redeem','adjust') NOT NULL,
    `points`      INT             NOT NULL,
    `source_type` VARCHAR(255)    NULL,
    `source_id`   BIGINT UNSIGNED NULL,
    `description` VARCHAR(255)    NULL,
    `user_id`     BIGINT UNSIGNED NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_loyalty_movements_company_client` (`company_id`, `client_id`),
    KEY `idx_loyalty_movements_source` (`source_type`, `source_id`),
    CONSTRAINT `loyalty_movements_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_movements_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `loyalty_movements_user_fk`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Caché del saldo de puntos en clientes
ALTER TABLE `clients`
    ADD COLUMN `points_balance` INT NOT NULL DEFAULT 0 AFTER `active`;
