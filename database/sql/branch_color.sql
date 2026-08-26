-- ============================================================
--  Rodex — Color de sucursal
--  Color en hexadecimal para usar como referencia visual.
-- ============================================================

ALTER TABLE `branches`
    ADD COLUMN `color` VARCHAR(7) NULL AFTER `manager_name`;
