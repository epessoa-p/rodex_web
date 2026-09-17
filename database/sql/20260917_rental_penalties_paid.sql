-- ============================================================
--  Rodex — Penalizaciones de alquiler con seguimiento de cobro
--  · rental_penalties.paid_amount: cuánto de la penalización ya se cobró
--    (estado derivado: pendiente / parcial / pagada).
--  · rental_payments.rental_penalty_id: a qué penalización se aplicó
--    un cobro de tipo 'penalizacion'.
--  Regulariza datos históricos:
--    - la mora cobrada al momento (accrueLateFee) queda como pagada;
--    - en contratos cerrados donde el depósito se aplicó a penalizaciones,
--      se registra esa aplicación como pago (método 'deposito') para que
--      el saldo del contrato deje de mostrar esas penalizaciones.
--  Seguro de re-ejecutar: la regularización solo corre al crear la columna.
-- ============================================================

SET @col_paid := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_penalties' AND COLUMN_NAME = 'paid_amount'
);
SET @sql := IF(@col_paid = 0,
    'ALTER TABLE `rental_penalties` ADD COLUMN `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `amount`',
    'SELECT "rental_penalties.paid_amount ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_pen := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_payments' AND COLUMN_NAME = 'rental_penalty_id'
);
SET @sql := IF(@col_pen = 0,
    'ALTER TABLE `rental_payments` ADD COLUMN `rental_penalty_id` BIGINT UNSIGNED NULL AFTER `rental_installment_id`, ADD KEY `idx_rental_payments_penalty` (`rental_penalty_id`), ADD CONSTRAINT `rental_payments_penalty_fk` FOREIGN KEY (`rental_penalty_id`) REFERENCES `rental_penalties`(`id`) ON DELETE SET NULL',
    'SELECT "rental_payments.rental_penalty_id ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Regularización (solo la primera vez) ─────────────────────
-- 1) Mora cobrada en el acto: se registró penalización + cobro juntos.
SET @sql := IF(@col_paid = 0,
    'UPDATE `rental_penalties` SET `paid_amount` = `amount` WHERE `concept` LIKE ''Mora por atraso · cuota %''',
    'SELECT "sin regularizar mora" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Contratos cerrados con depósito aplicado (total o parcial) a penalizaciones:
--    monto aplicado = deposit - deposit_refunded, repartido a las penalizaciones
--    aún sin cobrar (de la más antigua a la más nueva).
DROP TEMPORARY TABLE IF EXISTS tmp_rental_dep;
CREATE TEMPORARY TABLE tmp_rental_dep AS
SELECT c.id AS contract_id, c.company_id, c.created_by,
       LEAST(GREATEST(c.deposit - c.deposit_refunded, 0), GREATEST(c.total - c.paid_amount, 0)) AS applied
FROM rental_contracts c
WHERE @col_paid = 0
  AND c.deleted_at IS NULL
  AND c.status = 'cerrada'
  AND c.deposit_status IN ('aplicado', 'parcial')
  AND (c.deposit - c.deposit_refunded) > 0.009
  AND (c.total - c.paid_amount) > 0.009;

UPDATE rental_penalties p
JOIN (
    SELECT t.id,
           LEAST(t.pending, GREATEST(0, t.applied - (t.running - t.pending))) AS pay
    FROM (
        SELECT p.id, (p.amount - p.paid_amount) AS pending, d.applied,
               SUM(p.amount - p.paid_amount) OVER (PARTITION BY p.rental_contract_id ORDER BY p.penalty_date, p.id) AS running
        FROM rental_penalties p
        JOIN tmp_rental_dep d ON d.contract_id = p.rental_contract_id
        WHERE (p.amount - p.paid_amount) > 0.009
    ) t
) x ON x.id = p.id
SET p.paid_amount = p.paid_amount + x.pay
WHERE x.pay > 0.009;

INSERT INTO rental_payments (company_id, rental_contract_id, type, amount, method, payment_date, notes, user_id, created_at, updated_at)
SELECT d.company_id, d.contract_id, 'penalizacion', d.applied, 'deposito', CURDATE(),
       'Depósito de garantía aplicado a penalizaciones (regularización)', d.created_by, NOW(), NOW()
FROM tmp_rental_dep d
WHERE d.applied > 0.009;

UPDATE rental_contracts c
JOIN tmp_rental_dep d ON d.contract_id = c.id
SET c.paid_amount = c.paid_amount + d.applied
WHERE d.applied > 0.009;

UPDATE rental_contracts c
JOIN tmp_rental_dep d ON d.contract_id = c.id
SET c.payment_status = CASE
        WHEN c.paid_amount >= c.total AND c.total > 0 THEN 'pagada'
        WHEN c.paid_amount > 0 THEN 'parcial'
        ELSE 'pendiente' END;

DROP TEMPORARY TABLE IF EXISTS tmp_rental_dep;
