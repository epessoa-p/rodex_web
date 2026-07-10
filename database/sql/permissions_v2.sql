-- ============================================================
--  VR Motors — Permisos v2
--  Ejecutar DESPUÉS de tener los permisos base (IDs 1-12, 20)
--  Módulos: branches, products, warehouses, cargos, personal,
--           document_templates, cash_registers, cash
-- ============================================================

-- ── 1. INSERTAR PERMISOS FALTANTES ──────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES

-- Sucursales
('Ver Sucursales',      'branches.view',   'branches', NULL, NOW(), NOW()),
('Crear Sucursales',    'branches.create', 'branches', NULL, NOW(), NOW()),
('Editar Sucursales',   'branches.edit',   'branches', NULL, NOW(), NOW()),
('Eliminar Sucursales', 'branches.delete', 'branches', NULL, NOW(), NOW()),

-- Productos
('Ver Productos',      'products.view',   'products', NULL, NOW(), NOW()),
('Crear Productos',    'products.create', 'products', NULL, NOW(), NOW()),
('Editar Productos',   'products.edit',   'products', NULL, NOW(), NOW()),
('Eliminar Productos', 'products.delete', 'products', NULL, NOW(), NOW()),

-- Almacenes
('Ver Almacenes',      'warehouses.view',   'warehouses', NULL, NOW(), NOW()),
('Crear Almacenes',    'warehouses.create', 'warehouses', NULL, NOW(), NOW()),
('Editar Almacenes',   'warehouses.edit',   'warehouses', NULL, NOW(), NOW()),
('Eliminar Almacenes', 'warehouses.delete', 'warehouses', NULL, NOW(), NOW()),

-- Cargos
('Ver Cargos',      'cargos.view',   'cargos', NULL, NOW(), NOW()),
('Crear Cargos',    'cargos.create', 'cargos', NULL, NOW(), NOW()),
('Editar Cargos',   'cargos.edit',   'cargos', NULL, NOW(), NOW()),
('Eliminar Cargos', 'cargos.delete', 'cargos', NULL, NOW(), NOW()),

-- Personal
('Ver Personal',      'personal.view',   'personal', NULL, NOW(), NOW()),
('Crear Personal',    'personal.create', 'personal', NULL, NOW(), NOW()),
('Editar Personal',   'personal.edit',   'personal', NULL, NOW(), NOW()),
('Eliminar Personal', 'personal.delete', 'personal', NULL, NOW(), NOW()),

-- Plantillas de documento
('Ver Plantillas',      'document-templates.view',   'document_templates', NULL, NOW(), NOW()),
('Crear Plantillas',    'document-templates.create', 'document_templates', NULL, NOW(), NOW()),
('Editar Plantillas',   'document-templates.edit',   'document_templates', NULL, NOW(), NOW()),
('Eliminar Plantillas', 'document-templates.delete', 'document_templates', NULL, NOW(), NOW()),

-- Cajas (gestión administrativa)
('Ver Cajas',      'cash-registers.view',   'cash_registers', NULL, NOW(), NOW()),
('Crear Cajas',    'cash-registers.create', 'cash_registers', NULL, NOW(), NOW()),
('Editar Cajas',   'cash-registers.edit',   'cash_registers', NULL, NOW(), NOW()),
('Eliminar Cajas', 'cash-registers.delete', 'cash_registers', NULL, NOW(), NOW()),

-- Caja (operación: apertura/cierre)
('Operar Caja', 'cash.operate', 'cash', NULL, NOW(), NOW());


-- ── 2. ASIGNAR PERMISOS A ROLES ──────────────────────────────
--
--  Cadena: Personal → cargo_id → Cargo → role_id → Role → role_permission
--
--  Matriz de acceso:
--  ┌─────────────────────────┬───────┬───────┬─────────┬────────┬──────────┐
--  │ Permiso                 │ Admin │ Gerente│ Cajero  │Empleado│(sup_admin│
--  │                         │       │        │         │        │por flag) │
--  ├─────────────────────────┼───────┼───────┼─────────┼────────┼──────────┤
--  │ users.*                 │ v,c,e │  view  │  —      │  —     │  (all)   │
--  │ branches.*              │ all   │  view  │  view   │  view  │  (all)   │
--  │ products.*              │ all   │  all   │  view   │  view  │  (all)   │
--  │ warehouses.*            │ all   │  view  │  —      │  —     │  (all)   │
--  │ cargos.*                │ all   │  view  │  —      │  —     │  (all)   │
--  │ personal.*              │ all   │  v,c,e │  view   │  —     │  (all)   │
--  │ document-templates.*    │ all   │  view  │  —      │  —     │  (all)   │
--  │ cash-registers.*        │ all   │  view  │  —      │  —     │  (all)   │
--  │ cash.operate            │  ✓    │   ✓    │  ✓      │  —     │  (all)   │
--  │ reports.view            │  ✓    │   ✓    │  ✓      │  —     │  (all)   │
--  └─────────────────────────┴───────┴───────┴─────────┴────────┴──────────┘
--
--  NOTA: Super Admin no necesita role_permission (usa is_super_admin=1)
-- ─────────────────────────────────────────────────────────────

-- Rol: Administrador de Empresa (slug = 'admin')
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.slug IN (
    -- Usuarios
    'users.view', 'users.create', 'users.edit',
    -- Sucursales
    'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
    -- Productos
    'products.view', 'products.create', 'products.edit', 'products.delete',
    -- Almacenes
    'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
    -- Cargos
    'cargos.view', 'cargos.create', 'cargos.edit', 'cargos.delete',
    -- Personal
    'personal.view', 'personal.create', 'personal.edit', 'personal.delete',
    -- Plantillas
    'document-templates.view', 'document-templates.create',
    'document-templates.edit', 'document-templates.delete',
    -- Cajas (admin)
    'cash-registers.view', 'cash-registers.create',
    'cash-registers.edit', 'cash-registers.delete',
    -- Caja operación + reportes
    'cash.operate', 'reports.view'
  );

-- Rol: Gerente (slug = 'manager')
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN (
    'users.view',
    'branches.view',
    'products.view', 'products.create', 'products.edit', 'products.delete',
    'warehouses.view',
    'cargos.view',
    'personal.view', 'personal.create', 'personal.edit',
    'document-templates.view',
    'cash-registers.view',
    'cash.operate',
    'reports.view'
  );

-- Rol: Cajero (slug = 'cashier')
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'cashier'
  AND p.slug IN (
    'branches.view',
    'products.view',
    'personal.view',
    'cash.operate',
    'reports.view'
  );

-- Rol: Empleado (slug = 'employee')
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'employee'
  AND p.slug IN (
    'branches.view',
    'products.view',
    'personal.view'
  );


-- ── 3. VERIFICACIÓN ──────────────────────────────────────────
-- Ejecuta estas queries para confirmar que todo quedó correcto:

-- Ver todos los permisos agrupados por módulo:
-- SELECT module, GROUP_CONCAT(slug ORDER BY slug SEPARATOR ', ') AS permisos
-- FROM permissions WHERE deleted_at IS NULL
-- GROUP BY module ORDER BY module;

-- Ver permisos por rol:
-- SELECT r.name AS rol, GROUP_CONCAT(p.slug ORDER BY p.slug SEPARATOR ', ') AS permisos
-- FROM roles r
-- JOIN role_permission rp ON rp.role_id = r.id
-- JOIN permissions p ON p.id = rp.permission_id
-- GROUP BY r.name ORDER BY r.name;
