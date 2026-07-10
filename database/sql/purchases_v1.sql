-- ============================================================
--  VR Motors — Módulo de Compras v1
--  Flujo: Orden de Compra → Recepción → Factura → Cuenta por Pagar
--  Tesorería con cuentas de fondos para pagos a proveedores
-- ============================================================

-- ── 1. PROVEEDORES ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   BIGINT UNSIGNED NOT NULL,
    `name`         VARCHAR(255)    NOT NULL,
    `nit`          VARCHAR(50)     NULL COMMENT 'NIT / RUC',
    `contact_name` VARCHAR(255)    NULL,
    `phone`        VARCHAR(30)     NULL,
    `email`        VARCHAR(255)    NULL,
    `address`      VARCHAR(500)    NULL,
    `notes`        TEXT            NULL,
    `active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    `deleted_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_suppliers_company` (`company_id`),
    CONSTRAINT `suppliers_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. CUENTAS DE TESORERÍA ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `treasury_accounts` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`     BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(255)    NOT NULL,
    `type`           ENUM('cash','bank') NOT NULL DEFAULT 'cash',
    `bank_name`      VARCHAR(255)    NULL,
    `account_number` VARCHAR(100)    NULL,
    `balance`        DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `active`         TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    `deleted_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_treasury_acc_company` (`company_id`),
    CONSTRAINT `treasury_acc_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. MOVIMIENTOS DE TESORERÍA (inmutables) ─────────────────
CREATE TABLE IF NOT EXISTS `treasury_movements` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`          BIGINT UNSIGNED NOT NULL,
    `treasury_account_id` BIGINT UNSIGNED NOT NULL,
    `user_id`             BIGINT UNSIGNED NOT NULL,
    `type`                ENUM('in','out') NOT NULL,
    `category`            VARCHAR(60)     NOT NULL COMMENT 'capital_injection, supplier_payment, adjustment_in, adjustment_out, expense',
    `amount`              DECIMAL(15,2)   NOT NULL,
    `reference_type`      VARCHAR(255)    NULL,
    `reference_id`        BIGINT UNSIGNED NULL,
    `description`         VARCHAR(500)    NULL,
    `movement_date`       TIMESTAMP       NOT NULL,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_treasury_mov_account` (`treasury_account_id`),
    KEY `idx_treasury_mov_company_date` (`company_id`, `movement_date`),
    CONSTRAINT `treasury_mov_account_fk` FOREIGN KEY (`treasury_account_id`) REFERENCES `treasury_accounts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `treasury_mov_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `treasury_mov_user_fk`    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. ÓRDENES DE COMPRA ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`    BIGINT UNSIGNED NOT NULL,
    `supplier_id`   BIGINT UNSIGNED NOT NULL,
    `branch_id`     BIGINT UNSIGNED NULL,
    `code`          VARCHAR(50)     NOT NULL,
    `status`        ENUM('draft','sent','partial','received','cancelled') NOT NULL DEFAULT 'draft',
    `order_date`    DATE            NOT NULL,
    `expected_date` DATE            NULL,
    `subtotal`      DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `tax`           DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `total`         DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `notes`         TEXT            NULL,
    `created_by`    BIGINT UNSIGNED NOT NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    `deleted_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_po_company` (`company_id`),
    KEY `idx_po_supplier` (`supplier_id`),
    CONSTRAINT `po_company_fk`  FOREIGN KEY (`company_id`)  REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `po_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    CONSTRAINT `po_branch_fk`   FOREIGN KEY (`branch_id`)   REFERENCES `branches`(`id`) ON DELETE SET NULL,
    CONSTRAINT `po_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_order_id` BIGINT UNSIGNED NOT NULL,
    `product_id`        BIGINT UNSIGNED NOT NULL,
    `quantity`          DECIMAL(15,2)   NOT NULL,
    `unit_cost`         DECIMAL(15,2)   NOT NULL,
    `subtotal`          DECIMAL(15,2)   NOT NULL,
    `received_quantity` DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `created_at`        TIMESTAMP NULL,
    `updated_at`        TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_poi_order` (`purchase_order_id`),
    CONSTRAINT `poi_order_fk`   FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `poi_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. RECEPCIÓN DE MERCADERÍA ───────────────────────────────
CREATE TABLE IF NOT EXISTS `goods_receipts` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`        BIGINT UNSIGNED NOT NULL,
    `purchase_order_id` BIGINT UNSIGNED NOT NULL,
    `warehouse_id`      BIGINT UNSIGNED NOT NULL,
    `code`              VARCHAR(50)     NOT NULL,
    `receipt_date`      DATE            NOT NULL,
    `notes`             TEXT            NULL,
    `received_by`       BIGINT UNSIGNED NOT NULL,
    `created_at`        TIMESTAMP NULL,
    `updated_at`        TIMESTAMP NULL,
    `deleted_at`        TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_gr_company` (`company_id`),
    KEY `idx_gr_order` (`purchase_order_id`),
    CONSTRAINT `gr_company_fk`   FOREIGN KEY (`company_id`)  REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `gr_order_fk`     FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`),
    CONSTRAINT `gr_warehouse_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`),
    CONSTRAINT `gr_received_by_fk` FOREIGN KEY (`received_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
    `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `goods_receipt_id`       BIGINT UNSIGNED NOT NULL,
    `purchase_order_item_id` BIGINT UNSIGNED NULL,
    `product_id`             BIGINT UNSIGNED NOT NULL,
    `quantity`               DECIMAL(15,2)   NOT NULL,
    `unit_cost`              DECIMAL(15,2)   NOT NULL,
    `created_at`             TIMESTAMP NULL,
    `updated_at`             TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_gri_receipt` (`goods_receipt_id`),
    CONSTRAINT `gri_receipt_fk` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `gri_poi_fk`     FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items`(`id`) ON DELETE SET NULL,
    CONSTRAINT `gri_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. COMPRAS / FACTURAS ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `purchases` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`        BIGINT UNSIGNED NOT NULL,
    `supplier_id`       BIGINT UNSIGNED NOT NULL,
    `purchase_order_id` BIGINT UNSIGNED NULL,
    `code`              VARCHAR(50)     NOT NULL,
    `invoice_number`    VARCHAR(100)    NULL,
    `purchase_date`     DATE            NOT NULL,
    `subtotal`          DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `tax`               DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `total`             DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `paid_amount`       DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `payment_status`    ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
    `notes`             TEXT            NULL,
    `created_by`        BIGINT UNSIGNED NOT NULL,
    `created_at`        TIMESTAMP NULL,
    `updated_at`        TIMESTAMP NULL,
    `deleted_at`        TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_purchases_company` (`company_id`),
    KEY `idx_purchases_supplier` (`supplier_id`),
    KEY `idx_purchases_paystatus` (`payment_status`),
    CONSTRAINT `purchases_company_fk`  FOREIGN KEY (`company_id`)  REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `purchases_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    CONSTRAINT `purchases_po_fk`       FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE SET NULL,
    CONSTRAINT `purchases_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_items` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_id` BIGINT UNSIGNED NOT NULL,
    `product_id`  BIGINT UNSIGNED NOT NULL,
    `quantity`    DECIMAL(15,2)   NOT NULL,
    `unit_cost`   DECIMAL(15,2)   NOT NULL,
    `subtotal`    DECIMAL(15,2)   NOT NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pi_purchase` (`purchase_id`),
    CONSTRAINT `pi_purchase_fk` FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
    CONSTRAINT `pi_product_fk`  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. PAGOS A PROVEEDORES (abonos) ──────────────────────────
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`          BIGINT UNSIGNED NOT NULL,
    `purchase_id`         BIGINT UNSIGNED NOT NULL,
    `treasury_account_id` BIGINT UNSIGNED NOT NULL,
    `amount`              DECIMAL(15,2)   NOT NULL,
    `payment_date`        DATE            NOT NULL,
    `method`              VARCHAR(50)     NULL COMMENT 'efectivo, transferencia, cheque',
    `reference`           VARCHAR(100)    NULL,
    `notes`               VARCHAR(500)    NULL,
    `user_id`             BIGINT UNSIGNED NOT NULL,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sp_purchase` (`purchase_id`),
    CONSTRAINT `sp_company_fk`  FOREIGN KEY (`company_id`)  REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sp_purchase_fk` FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
    CONSTRAINT `sp_account_fk`  FOREIGN KEY (`treasury_account_id`) REFERENCES `treasury_accounts`(`id`),
    CONSTRAINT `sp_user_fk`     FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. PERMISOS ──────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Dashboard Compras', 'purchases-dashboard.view', 'purchases', NULL, NOW(), NOW()),
('Ver Proveedores',      'suppliers.view',   'suppliers', NULL, NOW(), NOW()),
('Crear Proveedores',    'suppliers.create', 'suppliers', NULL, NOW(), NOW()),
('Editar Proveedores',   'suppliers.edit',   'suppliers', NULL, NOW(), NOW()),
('Eliminar Proveedores', 'suppliers.delete', 'suppliers', NULL, NOW(), NOW()),
('Ver Tesorería',        'treasury.view',   'treasury', NULL, NOW(), NOW()),
('Gestionar Tesorería',  'treasury.manage', 'treasury', NULL, NOW(), NOW()),
('Ver Órdenes de Compra',   'purchase-orders.view',   'purchase_orders', NULL, NOW(), NOW()),
('Crear Órdenes de Compra', 'purchase-orders.create', 'purchase_orders', NULL, NOW(), NOW()),
('Editar Órdenes de Compra','purchase-orders.edit',   'purchase_orders', NULL, NOW(), NOW()),
('Eliminar Órdenes de Compra','purchase-orders.delete','purchase_orders', NULL, NOW(), NOW()),
('Ver Recepciones',   'goods-receipts.view',   'goods_receipts', NULL, NOW(), NOW()),
('Crear Recepciones', 'goods-receipts.create', 'goods_receipts', NULL, NOW(), NOW()),
('Ver Compras',      'purchases.view',   'purchases', NULL, NOW(), NOW()),
('Crear Compras',    'purchases.create', 'purchases', NULL, NOW(), NOW()),
('Editar Compras',   'purchases.edit',   'purchases', NULL, NOW(), NOW()),
('Eliminar Compras', 'purchases.delete', 'purchases', NULL, NOW(), NOW()),
('Ver Cuentas por Pagar',     'accounts-payable.view', 'accounts_payable', NULL, NOW(), NOW()),
('Registrar Pagos Proveedor', 'accounts-payable.pay',  'accounts_payable', NULL, NOW(), NOW());

-- ── 9. ASIGNAR A ROLES ───────────────────────────────────────
-- Admin: todos los permisos de compras
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'admin'
  AND p.module IN ('purchases','suppliers','treasury','purchase_orders','goods_receipts','accounts_payable');

-- Gerente: ver/crear/editar (sin eliminar, sin gestionar tesorería avanzada pero sí ver)
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.slug = 'manager'
  AND p.slug IN (
    'purchases-dashboard.view',
    'suppliers.view','suppliers.create','suppliers.edit',
    'treasury.view',
    'purchase-orders.view','purchase-orders.create','purchase-orders.edit',
    'goods-receipts.view','goods-receipts.create',
    'purchases.view','purchases.create','purchases.edit',
    'accounts-payable.view','accounts-payable.pay'
  );
