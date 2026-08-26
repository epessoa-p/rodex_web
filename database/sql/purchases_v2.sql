-- ============================================================
--  Rodex — Compras v2: vincular recepciones a facturas
--  Evita doble facturación de recepciones parciales de una OC
-- ============================================================

ALTER TABLE `goods_receipts`
    ADD COLUMN `purchase_id` BIGINT UNSIGNED NULL AFTER `purchase_order_id`;

ALTER TABLE `goods_receipts`
    ADD CONSTRAINT `gr_purchase_fk`
        FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE SET NULL;
