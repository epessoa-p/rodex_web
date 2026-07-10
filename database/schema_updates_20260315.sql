USE prestamos_db;

START TRANSACTION;

-- =========================================================
-- 1. SOFT DELETES EN TABLAS EXISTENTES
-- =========================================================

ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE companies ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE roles ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE permissions ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE loan_types ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE loans ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE loan_payments ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE clients ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

CREATE UNIQUE INDEX users_name_unique ON users (name);
CREATE UNIQUE INDEX clients_company_id_id_number_unique ON clients (company_id, id_number);

-- =========================================================
-- 2. NORMALIZAR PRESTAMOS PARA USAR SOLO client_id
-- =========================================================

ALTER TABLE loans ADD COLUMN client_id BIGINT UNSIGNED NULL AFTER loan_type_id;
CREATE INDEX loans_client_id_index ON loans (client_id);
ALTER TABLE loans
    ADD CONSTRAINT loans_client_id_foreign
    FOREIGN KEY (client_id) REFERENCES clients (id)
    ON DELETE SET NULL;

-- Crear clientes a partir de los datos históricos guardados en loans
INSERT INTO clients (
    company_id,
    name,
    id_number,
    phone,
    email,
    address,
    active,
    created_at,
    updated_at
)
SELECT DISTINCT
    loans.company_id,
    loans.client_name,
    loans.client_id_number,
    loans.client_phone,
    loans.client_email,
    loans.client_address,
    1,
    NOW(),
    NOW()
FROM loans
WHERE loans.client_name IS NOT NULL
  AND loans.client_name <> ''
  AND NOT EXISTS (
        SELECT 1
        FROM clients c
        WHERE c.company_id = loans.company_id
          AND c.name = loans.client_name
          AND COALESCE(c.id_number, '') = COALESCE(loans.client_id_number, '')
  );

UPDATE loans
INNER JOIN clients
    ON clients.company_id = loans.company_id
   AND clients.name = loans.client_name
   AND COALESCE(clients.id_number, '') = COALESCE(loans.client_id_number, '')
SET loans.client_id = clients.id
WHERE loans.client_id IS NULL;

-- Ejecuta estas líneas después de verificar que todos los préstamos ya tienen client_id
ALTER TABLE loans DROP COLUMN client_name;
ALTER TABLE loans DROP COLUMN client_id_number;
ALTER TABLE loans DROP COLUMN client_phone;
ALTER TABLE loans DROP COLUMN client_email;
ALTER TABLE loans DROP COLUMN client_address;

-- =========================================================
-- 3. SUCURSALES
-- =========================================================

CREATE TABLE branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    manager_name VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY branches_code_unique (code),
    KEY branches_company_id_name_index (company_id, name),
    CONSTRAINT branches_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. PRODUCTOS
-- =========================================================

CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'unidad',
    cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY products_sku_unique (sku),
    KEY products_company_id_name_index (company_id, name),
    CONSTRAINT products_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. ALMACENES
-- =========================================================

CREATE TABLE warehouses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    location VARCHAR(255) NULL,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY warehouses_code_unique (code),
    KEY warehouses_company_id_name_index (company_id, name),
    KEY warehouses_branch_id_index (branch_id),
    CONSTRAINT warehouses_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT warehouses_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 6. MOVIMIENTOS DE ALMACEN
-- =========================================================

CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    quantity DECIMAL(15,2) NOT NULL,
    unit_cost DECIMAL(15,2) NULL,
    reference VARCHAR(100) NULL,
    notes TEXT NULL,
    movement_date DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY inventory_movements_company_id_index (company_id),
    KEY inventory_movements_warehouse_id_index (warehouse_id),
    KEY inventory_movements_product_id_index (product_id),
    KEY inventory_movements_branch_id_index (branch_id),
    KEY inventory_movements_user_id_index (user_id),
    KEY inventory_movements_type_date_index (type, movement_date),
    CONSTRAINT inventory_movements_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT inventory_movements_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES warehouses (id) ON DELETE CASCADE,
    CONSTRAINT inventory_movements_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE SET NULL,
    CONSTRAINT inventory_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT inventory_movements_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;