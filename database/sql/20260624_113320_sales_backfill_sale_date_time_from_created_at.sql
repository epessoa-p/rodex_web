-- ============================================================================
--  Rodex — Backfill de la HORA en sales.sale_date desde created_at
-- ----------------------------------------------------------------------------
--  Las ventas históricas guardaban sale_date solo como fecha (hora 00:00:00).
--  Tras corregir created_at a hora de Bolivia, copiamos la HORA de created_at
--  a sale_date, conservando la FECHA original de la venta.
--
--   sale_date = FECHA(sale_date)  +  HORA(created_at)
--
--  ¡¡IMPORTANTE!!
--   1) Ejecutar DESPUÉS del script de corrección UTC→Bolivia de created_at.
--   2) Hacer BACKUP antes.
--   3) Solo afecta filas con hora 00:00:00 (las históricas), por lo que es
--      seguro re-ejecutarlo: las ventas nuevas (con hora real) no se tocan.
-- ============================================================================

START TRANSACTION;

UPDATE `sales`
SET `sale_date` = TIMESTAMP(DATE(`sale_date`), TIME(`created_at`))
WHERE `created_at` IS NOT NULL
  AND TIME(`sale_date`) = '00:00:00';

COMMIT;

-- ============================================================================
--  Verificación (opcional): debería devolver 0 filas con hora 00:00:00,
--  salvo ventas reales hechas exactamente a medianoche.
--    SELECT id, code, sale_date, created_at FROM sales
--    WHERE TIME(sale_date) = '00:00:00' ORDER BY id DESC;
-- ============================================================================
