-- ============================================================
--  VR Motors — Ventas v3
--  Devoluciones: registrar el efectivo realmente reembolsado
--  (distinto del valor de la mercadería devuelta `total`).
-- ============================================================

ALTER TABLE `sale_returns`
    ADD COLUMN `refunded_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `total`;
