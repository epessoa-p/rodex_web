-- =====================================================================
--  Rodex — Permisos: ver todos los registros (taller y ventas de motos)
--  Sin estos permisos, el personal solo ve en cada listado lo que él mismo
--  registró (created_by = usuario logueado).
--  Se asignan a admin y manager. Ejecutar UNA sola vez. MySQL 8.0+.
-- =====================================================================

INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver todas las órdenes de taller (no solo las propias)', 'workshop.view-all-records', 'workshop',
 'En el listado de órdenes, ver las de todo el personal; sin él, solo las que registró el usuario', NOW(), NOW()),
('Ver todas las ventas de motos (no solo las propias)', 'moto-sales.view-all-records', 'motos',
 'En el listado de ventas de motos, ver las de todo el personal; sin él, solo las que registró el usuario', NOW(), NOW());

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug IN ('workshop.view-all-records', 'moto-sales.view-all-records');
