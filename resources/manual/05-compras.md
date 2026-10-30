---
title: Compras y proveedores
icon: bi-receipt
summary: Registrar proveedores, órdenes de compra, recepciones, facturas y pagos a proveedores.
module: purchases
permissions: purchases.view, suppliers.view, purchase-orders.view, goods-receipts.view, accounts-payable.view, purchases-dashboard.view
routes: purchases.*, purchases-dashboard.*, suppliers.*, purchase-orders.*, goods-receipts.*, accounts-payable.*
---

## Para qué sirve

Con este módulo registras lo que compras a tus proveedores. **El stock sube** con cada compra, y llevas el control de lo que les **debes**.

Hay dos formas de trabajar:

- **Compra directa** (la más común): registras la factura del proveedor y el stock entra en ese momento.
- **Con orden de compra:** primero pides la mercadería (orden), luego la recibes (recepción) y por último registras la factura.

## Registrar un proveedor

En **Compras → Proveedores** presiona **Nuevo** y completa el nombre, el NIT, la persona de contacto, el teléfono y la dirección.

## Compra directa

1. Entra a **Compras → Compras** y crea una **Nueva factura de compra**.
2. Elige el **proveedor** y deja **— Sin orden de compra —**.
3. Elige el **almacén** donde entra la mercadería y escribe el **N° de factura**.
4. Agrega los productos con su **Cantidad** y **Costo unit.**
5. Si corresponde, agrega el **Descuento** o el **Impuesto**.
6. Revisa el **Total** y guarda. Los productos suman al stock de ese almacén.

> La compra queda **por pagar**. Lo que pagues después se registra como abono (ver más abajo).

## Compra con orden de compra

1. **Órdenes de Compra → Nueva:** elige el proveedor y los productos que pides.
2. **Recepciones → Recepcionar mercadería:**
   - elige la orden y el **almacén**;
   - en **Cant. a recibir** indica lo que realmente llegó.

   Puedes recibir por partes: el sistema muestra lo **Ordenado**, lo **Recibido** y lo **Pendiente**.
3. **Compras → Nueva factura:** elige la orden. Los productos se cargan desde las recepciones.

## Pagar a un proveedor

1. Entra a **Compras → Cuentas por Pagar**. Ahí ves las facturas pendientes con su **Total**, lo **Pagado** y el **Saldo**.
2. Presiona **Registrar pago** en la factura.
3. Indica el monto y el **Método de pago**. Elige de dónde sale el dinero:
   - de **tu caja abierta**, que registra el pago como egreso de caja;
   - o de una **cuenta** de Tesorería, como el banco.
4. Agrega notas si quieres y confirma.

La factura pasa a **parcial** o a **pagada** según el monto.

> También puedes pagar desde la barra superior: **Registrar gasto → Pago a proveedor**.

## Preguntas frecuentes

**Registré una compra con un costo equivocado.**
El costo del producto se usa para calcular las ganancias. Corrígelo en la ficha del producto y avisa al encargado para revisar la compra.

**La mercadería llegó incompleta.**
Usa una orden de compra y recepciona solo lo que llegó. Lo pendiente queda abierto para la siguiente recepción.
