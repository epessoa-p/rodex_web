-- =====================================================================
--  Rodex — Permisos del módulo de Fidelización
--  Fecha: 2026-06-20
--
--  Crea los permisos del módulo y los asigna a admin/manager (y a cashier
--  los de canje, para poder canjear desde el POS).
--  Ejecutar UNA sola vez en producción. MySQL 8.0+.
-- =====================================================================

-- ── 1. INSERTAR PERMISOS ─────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Dashboard de Fidelización', 'loyalty-dashboard.view',   'loyalty', NULL, NOW(), NOW()),
('Ver Configuración de Fidelización', 'loyalty-settings.view', 'loyalty', NULL, NOW(), NOW()),
('Editar Configuración de Fidelización', 'loyalty-settings.edit', 'loyalty', NULL, NOW(), NOW()),
('Ver Recompensas',    'loyalty-rewards.view',   'loyalty', NULL, NOW(), NOW()),
('Crear Recompensas',  'loyalty-rewards.create', 'loyalty', NULL, NOW(), NOW()),
('Editar Recompensas', 'loyalty-rewards.edit',   'loyalty', NULL, NOW(), NOW()),
('Eliminar Recompensas','loyalty-rewards.delete','loyalty', NULL, NOW(), NOW()),
('Ver Canjes',         'loyalty-redemptions.view', 'loyalty', NULL, NOW(), NOW()),
('Registrar Canje',    'loyalty.redeem',         'loyalty', NULL, NOW(), NOW()),
('Ver Movimientos de Puntos', 'loyalty-movements.view', 'loyalty', NULL, NOW(), NOW());

-- ── 2. ASIGNAR A admin y manager (todos) ─────────────────────
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug IN (
    'loyalty-dashboard.view', 'loyalty-settings.view', 'loyalty-settings.edit',
    'loyalty-rewards.view', 'loyalty-rewards.create', 'loyalty-rewards.edit', 'loyalty-rewards.delete',
    'loyalty-redemptions.view', 'loyalty.redeem', 'loyalty-movements.view'
  );

-- ── 3. ASIGNAR A cashier (canje desde el POS) ────────────────
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN ('loyalty.redeem', 'loyalty-rewards.view', 'loyalty-redemptions.view');

-- ── 4. VERIFICACIÓN (opcional) ────────────────────────────────
-- SELECT slug FROM permissions WHERE module = 'loyalty';
