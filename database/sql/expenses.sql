-- ============================================================
--  VR Motors — Gastos desde caja + catálogo de Servicios
-- ============================================================

-- ── 1. Catálogo de servicios de gasto ─────────────────────────
CREATE TABLE IF NOT EXISTS `expense_services` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`     BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(150)    NOT NULL,
    `type`           ENUM('basico','externo','transporte','otro') NOT NULL DEFAULT 'basico',
    `default_amount` DECIMAL(15,2)   NULL,
    `notes`          VARCHAR(500)    NULL,
    `active`         TINYINT(1)      NOT NULL DEFAULT 1,
    `created_by`     BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    `deleted_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_expsrv_company` (`company_id`),
    CONSTRAINT `expsrv_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Permitir pago a proveedor desde caja (sin tesorería) ───
ALTER TABLE `supplier_payments`
    MODIFY COLUMN `treasury_account_id` BIGINT UNSIGNED NULL;

-- ── 3. Permisos del catálogo ──────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver servicios de gasto',     'expense-services.view',   'expenses', NULL, NOW(), NOW()),
('Gestionar servicios de gasto','expense-services.manage', 'expenses', NULL, NOW(), NOW());

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager') AND p.module = 'expenses';
