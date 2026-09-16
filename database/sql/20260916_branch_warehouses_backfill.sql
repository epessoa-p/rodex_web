-- ============================================================
--  Rodex — Cada sucursal tiene SU almacén (creado por el sistema)
--  Desde 2026-09-16 al crear una sucursal el sistema crea su almacén y
--  al editarla copia nombre/dirección. Este script pone al día los datos
--  existentes: a toda sucursal SIN almacén (warehouse_id NULL o apuntando
--  a un almacén eliminado) le crea uno y lo enlaza.
--
--  NO cambia el esquema. Es idempotente: al volver a correr no encuentra
--  sucursales sin almacén y no hace nada.
--  Las sucursales que ya comparten un almacén con otra NO se tocan
--  (se respeta el stock existente).
-- ============================================================

-- 1. Crear el almacén de cada sucursal huérfana. Código único garantizado:
--    ALM-S{id de la sucursal} (no colisiona con ALM-001… del sistema).
INSERT INTO `warehouses` (`company_id`, `name`, `code`, `location`, `description`, `active`, `created_at`, `updated_at`)
SELECT b.`company_id`,
       b.`name`,
       CONCAT('ALM-S', b.`id`),
       b.`address`,
       CONCAT('Almacén de la sucursal ', b.`name`),
       1, NOW(), NOW()
FROM `branches` b
WHERE b.`deleted_at` IS NULL
  AND (b.`warehouse_id` IS NULL
       OR NOT EXISTS (SELECT 1 FROM `warehouses` w WHERE w.`id` = b.`warehouse_id` AND w.`deleted_at` IS NULL))
  AND NOT EXISTS (SELECT 1 FROM `warehouses` w2 WHERE w2.`company_id` = b.`company_id` AND w2.`code` = CONCAT('ALM-S', b.`id`));

-- 2. Enlazar la sucursal con el almacén recién creado (por el código ALM-S{id}).
UPDATE `branches` b
JOIN `warehouses` w
  ON w.`company_id` = b.`company_id`
 AND w.`code` = CONCAT('ALM-S', b.`id`)
 AND w.`deleted_at` IS NULL
SET b.`warehouse_id` = w.`id`
WHERE b.`deleted_at` IS NULL
  AND (b.`warehouse_id` IS NULL
       OR NOT EXISTS (SELECT 1 FROM `warehouses` w3 WHERE w3.`id` = b.`warehouse_id` AND w3.`deleted_at` IS NULL));
