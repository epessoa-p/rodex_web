-- ============================================================
-- Actualización de esquema - Lógica de negocio préstamos
-- Fecha: 2026-03-23
-- ============================================================

-- 1. Agregar campos de configuración de mora/multa en credit_categories
ALTER TABLE credit_categories
    ADD COLUMN penalty_grace_days INT NOT NULL DEFAULT 10 COMMENT 'Días de gracia después de vencer el plazo para pagar antes de pasar a venta' AFTER max_amount,
    ADD COLUMN penalty_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de multa por mora' AFTER penalty_grace_days,
    ADD COLUMN penalty_fixed_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto fijo de multa por mora (si aplica)' AFTER penalty_rate;

-- 2. Tabla de pagos de interés periódicos
CREATE TABLE IF NOT EXISTS loan_interest_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    period_number INT UNSIGNED NOT NULL COMMENT 'Número de periodo (1, 2, 3...)',
    period_start DATE NOT NULL COMMENT 'Inicio del periodo',
    period_end DATE NOT NULL COMMENT 'Fin del periodo',
    base_amount DECIMAL(12,2) NOT NULL COMMENT 'Capital base para calcular el interés',
    interest_rate DECIMAL(8,2) NOT NULL COMMENT 'Porcentaje de interés aplicado',
    interest_amount DECIMAL(12,2) NOT NULL COMMENT 'Monto de interés calculado',
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto pagado',
    status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    payment_method VARCHAR(50) NULL,
    reference VARCHAR(100) NULL,
    notes TEXT NULL,
    registered_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lip_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_lip_registered FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lip_loan_status (loan_id, status),
    INDEX idx_lip_period (loan_id, period_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de amortizaciones (abonos al capital)
CREATE TABLE IF NOT EXISTS loan_amortizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL COMMENT 'Monto amortizado al capital',
    capital_before DECIMAL(12,2) NOT NULL COMMENT 'Capital antes de la amortización',
    capital_after DECIMAL(12,2) NOT NULL COMMENT 'Capital después de la amortización',
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'efectivo',
    reference VARCHAR(100) NULL,
    notes TEXT NULL,
    registered_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_la_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_la_registered FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_la_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Agregar campo de capital actual en loans (saldo vigente)
ALTER TABLE loans
    ADD COLUMN current_capital DECIMAL(12,2) NULL COMMENT 'Capital vigente (se reduce con amortizaciones)' AFTER amount;

-- 5. Actualizar current_capital con el monto inicial en todos los préstamos existentes
UPDATE loans SET current_capital = amount WHERE current_capital IS NULL;

-- 6. Agregar campo de estado de mora
ALTER TABLE loans
    ADD COLUMN penalty_status ENUM('normal','overdue','grace','defaulted') NOT NULL DEFAULT 'normal' COMMENT 'Estado de mora del préstamo' AFTER status,
    ADD COLUMN penalty_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto acumulado de multas' AFTER penalty_status,
    ADD COLUMN last_interest_generated_at DATE NULL COMMENT 'Fecha del último interés generado' AFTER penalty_amount;

-- 7. Agregar campos de firma dual en loan_contracts
ALTER TABLE loan_contracts
    ADD COLUMN lender_signature_path VARCHAR(255) NULL COMMENT 'Ruta de firma del prestamista' AFTER signature_path,
    ADD COLUMN lender_signed_at DATETIME NULL AFTER lender_signature_path,
    ADD COLUMN lender_signed_by BIGINT UNSIGNED NULL AFTER lender_signed_at,
    ADD COLUMN client_signature_path VARCHAR(255) NULL COMMENT 'Ruta de firma del cliente' AFTER lender_signed_by,
    ADD COLUMN client_signed_at DATETIME NULL AFTER client_signature_path;

-- Renombrar signature_path existente a algo genérico si se quiere mantener compatibilidad
-- (mantenemos los campos existentes por compatibilidad)
