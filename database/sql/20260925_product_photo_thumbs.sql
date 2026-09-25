-- ============================================================
--  Rodex — Miniatura de la foto de producto
--  · product_photos.thumb_path: ruta de la miniatura (320 px, JPEG) que usan
--    los listados (móvil, Inventario, POS y catálogo público). NULL = todavía
--    no tiene y se sirve la foto original (fotos anteriores a este cambio).
--  Seguro de re-ejecutar: valida que la columna no exista.
-- ============================================================

SET @col_thumb := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_photos' AND COLUMN_NAME = 'thumb_path'
);
SET @sql := IF(@col_thumb = 0,
    'ALTER TABLE `product_photos` ADD COLUMN `thumb_path` VARCHAR(500) NULL AFTER `file_path`',
    'SELECT "product_photos.thumb_path ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
