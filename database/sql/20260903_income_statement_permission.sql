-- ============================================================
--  Rodex — Estado de resultados (permiso).
--  Reporte por movimientos de caja + tesorería.
--  Ejecutar UNA sola vez. Idempotente. MySQL 8.0+.
-- ============================================================

-- Permiso administrativo (módulo 'reports', no atado a plan).
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Estado de Resultados', 'income-statement.view', 'reports', NULL, NOW(), NOW());

-- Asignar a admin y gerente.
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug = 'income-statement.view';
