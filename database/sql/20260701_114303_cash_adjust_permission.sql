-- ============================================================
--  VR Motors — Permiso para ajustar diferencias de caja
--  Crea 'cash.adjust' y lo asigna a admin y manager.
--  (Super Admin no requiere asignación: usa is_super_admin=1.)
--  Seguro de re-ejecutar: usa INSERT IGNORE.
-- ============================================================

-- ── 1. INSERTAR PERMISO ──────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ajustar Diferencias de Caja', 'cash.adjust', 'cash', 'Registrar ajustes sobre la diferencia de un cierre de caja', NOW(), NOW());

-- ── 2. ASIGNAR A ROLES (admin y manager) ─────────────────────
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug = 'cash.adjust';

-- ── 3. VERIFICACIÓN (opcional) ───────────────────────────────
-- SELECT r.name AS rol, p.slug
-- FROM roles r
-- JOIN role_permission rp ON rp.role_id = r.id
-- JOIN permissions p ON p.id = rp.permission_id
-- WHERE p.slug = 'cash.adjust';
