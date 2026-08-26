-- =====================================================================
--  Rodex — Permisos: tabs de sucursal en Ventas + Dashboards
--  Fecha: 2026-06-19
--
--  Crea 4 permisos nuevos y los asigna a los roles admin y manager:
--    - sales.view-all-branches : ver todos los tabs/datos de sucursales
--                                en la lista de ventas (sin él, el usuario
--                                solo ve la sucursal de su caja y "TODOS"
--                                queda deshabilitado).
--    - sales-dashboard.view    : menú/ruta Dashboard de Ventas.
--    - workshop-dashboard.view : menú/ruta Dashboard de Taller.
--    - rentals-dashboard.view  : menú/ruta Dashboard de Alquileres.
--
--  IMPORTANTE: ejecutar en producción tras desplegar el código, porque las
--  rutas de dashboard ahora exigen estos permisos (admin/manager los reciben
--  aquí; el super admin pasa por is_super_admin).
--  Ejecutar UNA sola vez. MySQL 8.0+.
-- =====================================================================

-- ── 1. INSERTAR PERMISOS ─────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver todas las sucursales (ventas)', 'sales.view-all-branches', 'sales',    'Ver los tabs/datos de todas las sucursales en la lista de ventas', NOW(), NOW()),
('Ver Dashboard de Ventas',           'sales-dashboard.view',    'sales',    NULL, NOW(), NOW()),
('Ver Dashboard de Taller',           'workshop-dashboard.view', 'workshop', NULL, NOW(), NOW()),
('Ver Dashboard de Alquileres',       'rentals-dashboard.view',  'rentals',  NULL, NOW(), NOW());

-- ── 2. ASIGNAR A ROLES admin y manager ───────────────────────
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug IN ('admin', 'manager')
  AND p.slug IN (
    'sales.view-all-branches',
    'sales-dashboard.view',
    'workshop-dashboard.view',
    'rentals-dashboard.view'
  );

-- ── 3. VERIFICACIÓN (opcional) ────────────────────────────────
-- SELECT slug FROM permissions
-- WHERE slug IN ('sales.view-all-branches','sales-dashboard.view','workshop-dashboard.view','rentals-dashboard.view');
