-- =====================================================================
--  VR Motors — Permisos Fidelización Fase 2 (Campañas + Reportes)
--  Ejecutar UNA sola vez en producción. MySQL 8.0+.
-- =====================================================================

INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Campañas',    'loyalty-campaigns.view',   'loyalty', NULL, NOW(), NOW()),
('Crear Campañas',  'loyalty-campaigns.create', 'loyalty', NULL, NOW(), NOW()),
('Editar Campañas', 'loyalty-campaigns.edit',   'loyalty', NULL, NOW(), NOW()),
('Eliminar Campañas','loyalty-campaigns.delete','loyalty', NULL, NOW(), NOW()),
('Ver Reportes de Fidelización', 'loyalty-reports.view', 'loyalty', NULL, NOW(), NOW());

-- Asignar a admin y manager
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug IN (
    'loyalty-campaigns.view', 'loyalty-campaigns.create', 'loyalty-campaigns.edit',
    'loyalty-campaigns.delete', 'loyalty-reports.view'
  );
