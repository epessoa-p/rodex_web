-- ============================================================
--  Rodex — Módulo Agenda (Citas / Appointments) v1
--  Agendar servicios: un cliente reserva día/hora, se revisa la
--  disponibilidad y se registra la cita. Opcionalmente se convierte
--  en una Orden de Trabajo (OT).
--  Gateado por el plan 'workshop' (Taller).
--
--  EJECUTAR EN PRODUCCIÓN (una sola vez). Es idempotente:
--    CREATE TABLE IF NOT EXISTS + INSERT IGNORE.
-- ============================================================

-- ── 1. CITAS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `appointments` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`       BIGINT UNSIGNED NOT NULL,
    `branch_id`        BIGINT UNSIGNED NULL,
    `client_id`        BIGINT UNSIGNED NULL COMMENT 'Cliente registrado (opcional)',
    `vehicle_id`       BIGINT UNSIGNED NULL,
    `service_id`       BIGINT UNSIGNED NULL COMMENT 'Servicio del catálogo (opcional)',
    `mechanic_id`      BIGINT UNSIGNED NULL COMMENT 'Mecánico asignado (opcional)',
    `work_order_id`    BIGINT UNSIGNED NULL COMMENT 'OT generada al convertir la cita',
    `customer_name`    VARCHAR(255)    NULL COMMENT 'Nombre suelto si no hay cliente registrado',
    `customer_phone`   VARCHAR(30)     NULL,
    `title`            VARCHAR(255)    NULL COMMENT 'Servicio / motivo breve',
    `scheduled_at`     DATETIME        NOT NULL COMMENT 'Inicio de la cita',
    `duration_minutes` INT             NOT NULL DEFAULT 60,
    `status`           ENUM('programada','confirmada','completada','cancelada','no_asistio') NOT NULL DEFAULT 'programada',
    `notes`            TEXT            NULL,
    `created_by`       BIGINT UNSIGNED NULL,
    `created_at`       TIMESTAMP       NULL,
    `updated_at`       TIMESTAMP       NULL,
    `deleted_at`       TIMESTAMP       NULL,
    PRIMARY KEY (`id`),
    KEY `idx_appointments_company` (`company_id`),
    KEY `idx_appointments_company_date` (`company_id`, `scheduled_at`),
    KEY `idx_appointments_branch` (`branch_id`),
    KEY `idx_appointments_status` (`status`),
    CONSTRAINT `appointments_company_fk`  FOREIGN KEY (`company_id`)    REFERENCES `companies`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `appointments_branch_fk`   FOREIGN KEY (`branch_id`)     REFERENCES `branches`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `appointments_client_fk`   FOREIGN KEY (`client_id`)     REFERENCES `clients`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `appointments_vehicle_fk`  FOREIGN KEY (`vehicle_id`)    REFERENCES `vehicles`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `appointments_service_fk`  FOREIGN KEY (`service_id`)    REFERENCES `services`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `appointments_mechanic_fk` FOREIGN KEY (`mechanic_id`)   REFERENCES `mechanics`(`id`)   ON DELETE SET NULL,
    CONSTRAINT `appointments_wo_fk`       FOREIGN KEY (`work_order_id`) REFERENCES `work_orders`(`id`) ON DELETE SET NULL,
    CONSTRAINT `appointments_user_fk`     FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. PERMISOS ─────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Agenda',      'appointments.view',   'appointments', NULL, NOW(), NOW()),
('Crear Citas',     'appointments.create', 'appointments', NULL, NOW(), NOW()),
('Editar Citas',    'appointments.edit',   'appointments', NULL, NOW(), NOW()),
('Eliminar Citas',  'appointments.delete', 'appointments', NULL, NOW(), NOW());

-- ── 3. ASIGNAR A ROLES ──────────────────────────────────────
-- Admin: todos los permisos de agenda
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module = 'appointments';

-- Gerente: ver/crear/editar (sin eliminar)
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN ('appointments.view', 'appointments.create', 'appointments.edit');
