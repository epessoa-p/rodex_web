-- ============================================================
--  VR Motors — Ventas: sale_date pasa de DATE a DATETIME
--  Objetivo: conservar la HORA de la venta (zona horaria Bolivia).
--  Ejecutar en producción una sola vez.
-- ============================================================

-- Los valores existentes (solo fecha) quedan a las 00:00:00; los nuevos
-- registros guardan la hora real de la venta.
ALTER TABLE `sales`
    MODIFY `sale_date` DATETIME NOT NULL;
