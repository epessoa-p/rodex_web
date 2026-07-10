USE prestamos_db;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS cargos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY cargos_company_name_unique (company_id, name),
    KEY cargos_company_id_index (company_id),
    KEY cargos_role_id_index (role_id),
    CONSTRAINT cargos_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE,
    CONSTRAINT cargos_role_id_foreign
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    cargo_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    id_number VARCHAR(50) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    hire_date DATE NULL,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY personals_user_id_unique (user_id),
    UNIQUE KEY personals_company_id_number_unique (company_id, id_number),
    KEY personals_company_id_index (company_id),
    KEY personals_cargo_id_index (cargo_id),
    CONSTRAINT personals_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE,
    CONSTRAINT personals_cargo_id_foreign
        FOREIGN KEY (cargo_id) REFERENCES cargos (id)
        ON DELETE RESTRICT,
    CONSTRAINT personals_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
