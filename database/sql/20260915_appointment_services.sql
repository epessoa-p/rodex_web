-- ============================================================
--  Rodex — Agenda: varios servicios por cita
--  Una cita puede llevar más de un servicio del catálogo; al convertirla
--  en OT se copian como líneas de work_order_services.
--  Se CONSERVA appointments.service_id (= primer servicio) por
--  compatibilidad con la web y con la APK ya instalada.
--
--  EJECUTAR EN PRODUCCIÓN (una sola vez). Es idempotente:
--    CREATE TABLE IF NOT EXISTS + INSERT IGNORE.
--  Requiere que ya exista `appointments` (20260829_agenda_v1.sql).
-- ============================================================

CREATE TABLE IF NOT EXISTS `appointment_services` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `appointment_id` BIGINT UNSIGNED NOT NULL,
    `service_id`     BIGINT UNSIGNED NOT NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appointment_service` (`appointment_id`, `service_id`),
    KEY `idx_appointment_services_service` (`service_id`),
    CONSTRAINT `appointment_services_appointment_fk` FOREIGN KEY (`appointment_id`)
        REFERENCES `appointments`(`id`) ON DELETE CASCADE,
    CONSTRAINT `appointment_services_service_fk` FOREIGN KEY (`service_id`)
        REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill: el servicio único que ya tenían las citas pasa a la tabla pivote.
INSERT IGNORE INTO `appointment_services` (`appointment_id`, `service_id`, `created_at`, `updated_at`)
SELECT a.`id`, a.`service_id`, NOW(), NOW()
FROM `appointments` a
WHERE a.`service_id` IS NOT NULL;
