-- ============================================================================
--  VR Motors — Corrección de marcas de tiempo históricas (UTC → Bolivia)
-- ----------------------------------------------------------------------------
--  La aplicación se ejecutaba con timezone UTC. Ahora usa America/La_Paz
--  (UTC-4). Los registros creados ANTES del cambio guardaron sus horas en
--  UTC, por lo que se muestran 4 horas adelantadas. Este script resta 4 horas
--  a las columnas que almacenan un MOMENTO EXACTO (created_at, updated_at,
--  deleted_at y eventos como opened_at/closed_at/delivered_at, etc.).
--
--  NO se tocan las columnas de tipo fecha (sale_date, movement_date), porque
--  se guardaban sin hora (00:00:00) y restarles 4h las pasaría al día anterior.
--
--  ¡¡IMPORTANTE!!
--   1) EJECUTAR UNA SOLA VEZ. Correrlo dos veces restaría 8 horas.
--   2) Hacer BACKUP de la base de datos antes de ejecutar.
--   3) Ejecutar inmediatamente después de desplegar el cambio de timezone,
--      antes de registrar nuevos movimientos (los nuevos ya quedan en hora
--      de Bolivia y NO deben corregirse).
-- ============================================================================

START TRANSACTION;

-- ── Catálogos / configuración ───────────────────────────────────────────────
UPDATE `branches`            SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `companies`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `company_user`        SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `roles`               SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `permissions`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `cargos`              SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `document_templates`  SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `payment_plans`       SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `warehouses`          SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);

-- ── Usuarios / personal ─────────────────────────────────────────────────────
UPDATE `users`               SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), email_verified_at = DATE_SUB(email_verified_at, INTERVAL 4 HOUR);
UPDATE `personal`            SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `personals`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `mechanics`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `promoters`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `commissions`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), paid_at = DATE_SUB(paid_at, INTERVAL 4 HOUR);

-- ── Clientes ────────────────────────────────────────────────────────────────
UPDATE `clients`             SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `client_documents`    SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);

-- ── Productos / inventario ──────────────────────────────────────────────────
UPDATE `products`            SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `product_brands`      SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `product_categories`  SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `product_photos`      SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `inventory_movements` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR); -- NO se toca movement_date (fecha)

-- ── Compras / proveedores ───────────────────────────────────────────────────
UPDATE `suppliers`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `supplier_payments`   SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `purchases`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `purchase_items`      SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `purchase_orders`     SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `purchase_order_items` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `goods_receipts`      SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `goods_receipt_items` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);

-- ── Ventas ──────────────────────────────────────────────────────────────────
UPDATE `sales`               SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR); -- NO se toca sale_date (datos históricos eran fecha sin hora)
UPDATE `sale_items`          SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `sale_details`        SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `sale_installments`   SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `sale_payments`       SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `sale_returns`        SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `sale_return_items`   SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `quotes`              SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `quote_items`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `credit_applications` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `warranties`          SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);

-- ── Caja / tesorería ────────────────────────────────────────────────────────
UPDATE `cajas`                  SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `cash_registers`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `cash_register_sessions` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), opened_at = DATE_SUB(opened_at, INTERVAL 4 HOUR), closed_at = DATE_SUB(closed_at, INTERVAL 4 HOUR);
UPDATE `cash_movements`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR); -- NO se toca movement_date (fecha)
UPDATE `petty_cashes`           SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `petty_cash_movements`   SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `treasury_accounts`      SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `treasury_movements`     SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR); -- NO se toca movement_date (fecha)
UPDATE `expense_services`       SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);

-- ── Motos ───────────────────────────────────────────────────────────────────
UPDATE `moto_brands`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `moto_models`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `moto_model_product`  SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `moto_units`          SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), delivered_at = DATE_SUB(delivered_at, INTERVAL 4 HOUR);
UPDATE `vehicles`            SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);

-- ── Taller ──────────────────────────────────────────────────────────────────
UPDATE `services`                SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `work_orders`             SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), delivered_at = DATE_SUB(delivered_at, INTERVAL 4 HOUR);
UPDATE `work_order_services`     SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `work_order_parts`        SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `work_order_payments`     SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `work_order_installments` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `trackings`               SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), completed_at = DATE_SUB(completed_at, INTERVAL 4 HOUR);

-- ── Alquileres ──────────────────────────────────────────────────────────────
UPDATE `rental_contracts`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR), delivered_at = DATE_SUB(delivered_at, INTERVAL 4 HOUR), returned_at = DATE_SUB(returned_at, INTERVAL 4 HOUR);
UPDATE `rental_inspections`       SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `rental_inspection_photos` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `rental_installments`      SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `rental_payments`          SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `rental_penalties`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);

-- ── Fidelización ────────────────────────────────────────────────────────────
UPDATE `loyalty_settings`        SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR);
UPDATE `loyalty_rewards`         SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `loyalty_campaigns`       SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), deleted_at = DATE_SUB(deleted_at, INTERVAL 4 HOUR);
UPDATE `loyalty_point_movements` SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), expires_at = DATE_SUB(expires_at, INTERVAL 4 HOUR);
UPDATE `loyalty_redemptions`     SET created_at = DATE_SUB(created_at, INTERVAL 4 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 4 HOUR), redeemed_at = DATE_SUB(redeemed_at, INTERVAL 4 HOUR);

COMMIT;

-- ============================================================================
--  Si algo sale mal antes del COMMIT, ejecutar:  ROLLBACK;
-- ============================================================================
