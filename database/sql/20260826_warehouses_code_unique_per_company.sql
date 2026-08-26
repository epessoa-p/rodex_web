-- =====================================================================
-- Rodex · Almacenes: código único POR EMPRESA (no global)
-- =====================================================================
-- Problema: `warehouses.code` tenía un índice único GLOBAL
-- (`warehouses_code_unique`), por lo que dos empresas distintas chocaban
-- con el mismo código (ej. ALM-01) al crear un almacén.
--
-- Solución: reemplazarlo por un índice único COMPUESTO (company_id, code),
-- acorde al multi-tenant. Cada empresa maneja su propia numeración.
--
-- Seguro de ejecutar: como el código era único global, NO existen filas
-- duplicadas (company_id, code); el índice compuesto no puede fallar.
-- El script es idempotente (se puede correr más de una vez sin error).
--
-- Ejecutar en la base de PRODUCCIÓN (una sola vez).
-- =====================================================================

-- 1) Eliminar el índice único global si todavía existe.
SET @sql_drop := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `warehouses` DROP INDEX `warehouses_code_unique`',
        'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name  = 'warehouses'
      AND index_name  = 'warehouses_code_unique'
);
PREPARE stmt FROM @sql_drop; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Crear el índice único compuesto (company_id, code) si no existe.
SET @sql_add := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `warehouses` ADD UNIQUE KEY `warehouses_company_id_code_unique` (`company_id`, `code`)',
        'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name  = 'warehouses'
      AND index_name  = 'warehouses_company_id_code_unique'
);
PREPARE stmt FROM @sql_add; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Verificación (opcional): debe listar SOLO el índice compuesto sobre code.
-- SHOW INDEX FROM `warehouses` WHERE Column_name = 'code';
