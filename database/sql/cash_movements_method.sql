-- ============================================================
--  VR Motors — Medio de pago en movimientos de caja
-- ============================================================

ALTER TABLE `cash_movements`
    ADD COLUMN `method` VARCHAR(50) NULL AFTER `amount`;
