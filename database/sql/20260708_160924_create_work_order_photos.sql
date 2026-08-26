-- ============================================================
--  Rodex — Fotos de recepción (work_order_photos)
--  Documentan el estado del vehículo al recibirlo en el taller.
--  Ligadas a la orden de trabajo (recepción).
--
--  NOTA: si un intento anterior falló con
--        "#1005 ... errno 121 (Duplicate key on write or update)",
--        es porque quedaron nombres de clave foránea ocupados en
--        InnoDB. Este script los limpia (DROP) y usa nombres nuevos.
-- ============================================================

-- Elimina cualquier tabla/definición parcial de un intento previo.
DROP TABLE IF EXISTS `work_order_photos`;

CREATE TABLE `work_order_photos` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`    BIGINT UNSIGNED NOT NULL,
    `work_order_id` BIGINT UNSIGNED NOT NULL,
    `file_path`     VARCHAR(255)    NOT NULL,
    `file_name`     VARCHAR(255)    NULL,
    `sort_order`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_work_order_photos_company` (`company_id`),
    KEY `idx_work_order_photos_order` (`work_order_id`),
    CONSTRAINT `fk_work_order_photos_company` FOREIGN KEY (`company_id`)    REFERENCES `companies`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_work_order_photos_order`   FOREIGN KEY (`work_order_id`) REFERENCES `work_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
