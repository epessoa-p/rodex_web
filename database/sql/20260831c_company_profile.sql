-- ============================================================
--  Rodex — "Mi empresa": vigencia del enlace de seguimiento +
--  permisos para que la empresa edite sus propios datos.
--
--  Ejecutar UNA sola vez en producción. Idempotente. MySQL 8.0+.
-- ============================================================

-- 1) companies.tracking_link_days (días de vigencia del link tras entregar)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'tracking_link_days');
SET @sql := IF(@col = 0,
    'ALTER TABLE `companies` ADD COLUMN `tracking_link_days` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `description`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Permisos (módulo administrativo 'company', siempre disponible sin plan)
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Mi Empresa',    'company-profile.view', 'company', NULL, NOW(), NOW()),
('Editar Mi Empresa', 'company-profile.edit', 'company', NULL, NOW(), NOW());

-- 3) Asignar al rol admin (dueño)
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN ('company-profile.view', 'company-profile.edit');
