-- ============================================================
--  VR Motors — Productos: compatibilidad con Modelos de motos
-- ============================================================

-- ── 1. Pivote producto ↔ modelo de moto ──────────────────────
CREATE TABLE IF NOT EXISTS `moto_model_product` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id`    BIGINT UNSIGNED NOT NULL,
    `moto_model_id` BIGINT UNSIGNED NOT NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_moto_model_product` (`product_id`, `moto_model_id`),
    KEY `idx_mmp_model` (`moto_model_id`),
    CONSTRAINT `mmp_product_fk` FOREIGN KEY (`product_id`)    REFERENCES `products`(`id`)     ON DELETE CASCADE,
    CONSTRAINT `mmp_model_fk`   FOREIGN KEY (`moto_model_id`) REFERENCES `moto_models`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Permitir modelos sin marca (creados por el importador) ─
ALTER TABLE `moto_models`
    MODIFY COLUMN `moto_brand_id` BIGINT UNSIGNED NULL;

-- ── 3. Eliminar campos de texto reemplazados por la relación ──
ALTER TABLE `products`
    DROP COLUMN `moto`,
    DROP COLUMN `compatible_models`;
