USE prestamos_db;

START TRANSACTION;

-- Quitar relación antigua warehouse -> branch
-- Se usa IF EXISTS para evitar fallos en entornos con nombres distintos o ya migrados.

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'warehouses'
      AND CONSTRAINT_NAME = 'warehouses_branch_id_foreign'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_drop_fk := IF(@fk_exists > 0,
    'ALTER TABLE warehouses DROP FOREIGN KEY warehouses_branch_id_foreign',
    'SELECT 1');
PREPARE stmt FROM @sql_drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'warehouses'
      AND INDEX_NAME = 'warehouses_branch_id_index'
);

SET @sql_drop_idx := IF(@idx_exists > 0,
    'ALTER TABLE warehouses DROP INDEX warehouses_branch_id_index',
    'SELECT 1');
PREPARE stmt2 FROM @sql_drop_idx;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'warehouses'
      AND COLUMN_NAME = 'branch_id'
);

SET @sql_drop_col := IF(@col_exists > 0,
    'ALTER TABLE warehouses DROP COLUMN branch_id',
    'SELECT 1');
PREPARE stmt3 FROM @sql_drop_col;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

COMMIT;
