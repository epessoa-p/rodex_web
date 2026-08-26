-- ============================================================
--  Rodex — Módulo de Clientes v1
--  Ejecutar en orden: tablas → permisos → role_permission
-- ============================================================

-- ── 1. TABLAS ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `clients` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`    BIGINT UNSIGNED NOT NULL,
    `full_name`     VARCHAR(255)    NOT NULL,
    `id_number`     VARCHAR(50)     NULL COMMENT 'CI / DNI / RUC',
    `phone`         VARCHAR(20)     NULL,
    `email`         VARCHAR(255)    NULL,
    `address`       VARCHAR(500)    NULL,
    `location_link` VARCHAR(1000)   NULL COMMENT 'URL Google Maps u otro',
    `photo`         VARCHAR(500)    NULL COMMENT 'Ruta en storage/public',
    `notes`         TEXT            NULL,
    `active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `created_by`    BIGINT UNSIGNED NOT NULL,
    `created_at`    TIMESTAMP       NULL,
    `updated_at`    TIMESTAMP       NULL,
    `deleted_at`    TIMESTAMP       NULL,
    PRIMARY KEY (`id`),
    KEY `idx_clients_company` (`company_id`),
    KEY `idx_clients_id_number` (`id_number`),
    CONSTRAINT `clients_company_fk`     FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `clients_created_by_fk`  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `client_documents` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`   BIGINT UNSIGNED NOT NULL,
    `company_id`  BIGINT UNSIGNED NOT NULL,
    `type`        ENUM('ci_front','ci_back','other') NOT NULL DEFAULT 'other',
    `label`       VARCHAR(255)    NULL COMMENT 'Nombre personalizado para tipo "other"',
    `file_path`   VARCHAR(500)    NOT NULL,
    `file_name`   VARCHAR(255)    NULL,
    `created_at`  TIMESTAMP       NULL,
    `updated_at`  TIMESTAMP       NULL,
    PRIMARY KEY (`id`),
    KEY `idx_client_docs_client` (`client_id`),
    CONSTRAINT `client_docs_client_fk`  FOREIGN KEY (`client_id`)  REFERENCES `clients`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `client_docs_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2. PERMISOS ───────────────────────────────────────────────

INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Clientes',      'clients.view',   'clients', NULL, NOW(), NOW()),
('Crear Clientes',    'clients.create', 'clients', NULL, NOW(), NOW()),
('Editar Clientes',   'clients.edit',   'clients', NULL, NOW(), NOW()),
('Eliminar Clientes', 'clients.delete', 'clients', NULL, NOW(), NOW());


-- ── 3. ASIGNAR PERMISOS A ROLES ──────────────────────────────

-- Administrador: acceso total
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN ('clients.view','clients.create','clients.edit','clients.delete');

-- Gerente: ver, crear, editar (no eliminar)
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN ('clients.view','clients.create','clients.edit');

-- Cajero: solo ver
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('clients.view');

-- Empleado: solo ver
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'employee'
  AND p.slug IN ('clients.view');
