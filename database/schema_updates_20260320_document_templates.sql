-- =============================================================
-- Plantillas de Documentos (contratos, boletas, recibos, etc.)
-- Fecha: 2026-03-20
-- =============================================================

CREATE TABLE IF NOT EXISTS document_templates (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id      BIGINT UNSIGNED NULL COMMENT 'NULL = plantilla global (super admin)',
    name            VARCHAR(255) NOT NULL,
    type            ENUM('contrato','boleta','recibo','amortizacion','liquidacion','otro') NOT NULL DEFAULT 'contrato',
    description     TEXT NULL,
    content         LONGTEXT NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    CONSTRAINT fk_dt_company  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_dt_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dt_company_type (company_id, type),
    INDEX idx_dt_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
