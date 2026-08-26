-- =====================================================================
--  Rodex — Fidelización: token público para el catálogo de premios
--  Equivale a: 2026_06_20_000003_add_public_token_to_loyalty_settings.php
--  Ejecutar UNA sola vez en producción. MySQL 8.0+.
-- =====================================================================

ALTER TABLE `loyalty_settings`
    ADD COLUMN `public_token` VARCHAR(40) NULL AFTER `points_label`,
    ADD UNIQUE KEY `loyalty_settings_public_token_unique` (`public_token`);
