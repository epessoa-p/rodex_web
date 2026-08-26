-- ============================================================
--  Rodex — Alquileres: Renta periódica + Fase 2
-- ============================================================

-- ── 1. Ajustes a rental_contracts ────────────────────────────
ALTER TABLE `rental_contracts`
    ADD COLUMN `payment_mode`     ENUM('renta','unico') NOT NULL DEFAULT 'renta' AFTER `daily_rate`,
    ADD COLUMN `billing_period`   ENUM('diario','semanal','mensual') NULL AFTER `payment_mode`,
    ADD COLUMN `period_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `billing_period`,
    ADD COLUMN `late_fee_per_day` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `period_amount`;

-- ── 2. Cuotas de renta ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rental_installments` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`         BIGINT UNSIGNED NOT NULL,
    `rental_contract_id` BIGINT UNSIGNED NOT NULL,
    `number`             INT             NOT NULL DEFAULT 1,
    `period_label`       VARCHAR(60)     NULL,
    `period_start`       DATE            NULL,
    `period_end`         DATE            NULL,
    `due_date`           DATE            NOT NULL,
    `amount`             DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `paid_amount`        DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `late_fee_charged`   DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `status`             ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
    `created_at`         TIMESTAMP NULL,
    `updated_at`         TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rental_inst_contract` (`rental_contract_id`),
    KEY `idx_rental_inst_due` (`due_date`, `status`),
    CONSTRAINT `rental_inst_company_fk`  FOREIGN KEY (`company_id`)         REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_inst_contract_fk` FOREIGN KEY (`rental_contract_id`) REFERENCES `rental_contracts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Vincular pagos a cuota ────────────────────────────────
ALTER TABLE `rental_payments`
    ADD COLUMN `rental_installment_id` BIGINT UNSIGNED NULL AFTER `rental_contract_id`,
    ADD CONSTRAINT `rental_payments_inst_fk` FOREIGN KEY (`rental_installment_id`) REFERENCES `rental_installments`(`id`) ON DELETE SET NULL;

-- ── 4. Inspecciones ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rental_inspections` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`         BIGINT UNSIGNED NOT NULL,
    `rental_contract_id` BIGINT UNSIGNED NOT NULL,
    `type`               ENUM('salida','entrada') NOT NULL,
    `mileage`            INT             NULL,
    `fuel_level`         VARCHAR(20)     NULL,
    `checklist`          JSON            NULL,
    `notes`              TEXT            NULL,
    `created_by`         BIGINT UNSIGNED NOT NULL,
    `created_at`         TIMESTAMP NULL,
    `updated_at`         TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rental_insp_contract` (`rental_contract_id`),
    CONSTRAINT `rental_insp_company_fk`  FOREIGN KEY (`company_id`)         REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_insp_contract_fk` FOREIGN KEY (`rental_contract_id`) REFERENCES `rental_contracts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_insp_creator_fk`  FOREIGN KEY (`created_by`)         REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Fotos de inspección ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `rental_inspection_photos` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`           BIGINT UNSIGNED NOT NULL,
    `rental_inspection_id` BIGINT UNSIGNED NOT NULL,
    `file_path`            VARCHAR(255)    NOT NULL,
    `file_name`            VARCHAR(255)    NULL,
    `sort_order`           INT             NOT NULL DEFAULT 0,
    `created_at`           TIMESTAMP NULL,
    `updated_at`           TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rental_insp_photo` (`rental_inspection_id`),
    CONSTRAINT `rental_insp_photo_company_fk`    FOREIGN KEY (`company_id`)           REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `rental_insp_photo_inspection_fk` FOREIGN KEY (`rental_inspection_id`) REFERENCES `rental_inspections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
