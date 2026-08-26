-- ============================================================
--  Rodex — Módulo de Estadísticas (permiso)
-- ============================================================

INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Estadísticas', 'statistics.view', 'statistics', 'Acceso al módulo de estadísticas y análisis', NOW(), NOW());

-- Asignar a admin y manager
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager') AND p.slug = 'statistics.view';
