-- ============================================================
--  Rodex — Fix cash_movements
--  La tabla legacy no coincidía con el modelo CashMovement.
--  Se recrea con el esquema correcto (tabla estaba vacía).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `cash_movements`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `cash_movements` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`               BIGINT UNSIGNED NOT NULL,
    `cash_register_id`         BIGINT UNSIGNED NOT NULL,
    `cash_register_session_id` BIGINT UNSIGNED NOT NULL,
    `user_id`                  BIGINT UNSIGNED NOT NULL,
    `type`                     ENUM('income','expense') NOT NULL,
    `category`                 VARCHAR(60)     NOT NULL,
    `amount`                   DECIMAL(15,2)   NOT NULL,
    `reference_type`           VARCHAR(255)    NULL,
    `reference_id`             BIGINT UNSIGNED NULL,
    `description`              VARCHAR(500)    NULL,
    `movement_date`            TIMESTAMP       NOT NULL,
    `created_at`               TIMESTAMP NULL,
    `updated_at`               TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cm_session` (`cash_register_session_id`),
    KEY `idx_cm_company` (`company_id`),
    CONSTRAINT `cm_company_fk`  FOREIGN KEY (`company_id`)               REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `cm_register_fk` FOREIGN KEY (`cash_register_id`)         REFERENCES `cash_registers`(`id`) ON DELETE CASCADE,
    CONSTRAINT `cm_session_fk`  FOREIGN KEY (`cash_register_session_id`) REFERENCES `cash_register_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `cm_user_fk`     FOREIGN KEY (`user_id`)                  REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
