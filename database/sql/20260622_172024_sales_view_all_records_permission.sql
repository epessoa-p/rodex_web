-- =====================================================================
--  VR Motors — Permiso: ver todas las ventas del listado
--  Sin este permiso, el personal solo ve en el listado las ventas que
--  él mismo registró (created_by = usuario logueado).
--  Se asigna a admin y manager. Ejecutar UNA sola vez. MySQL 8.0+.
-- =====================================================================

INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver todas las ventas (no solo las propias)', 'sales.view-all-records', 'sales',
 'En el listado de ventas, ver las de todo el personal; sin él, solo las que registró el usuario', NOW(), NOW());

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug = 'sales.view-all-records';
