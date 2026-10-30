---
title: Créditos y cobranza
icon: bi-credit-card
summary: Seguir las ventas a crédito, cobrar cuotas y controlar a los clientes morosos.
module: sales
permissions: credit.view, credit.collect, credit-applications.view, payment-plans.view, credit-reports.view
routes: credit.*, credit-applications.*, payment-plans.*
---

## Para qué sirve

Cuando vendes **a crédito** desde el POS, el sistema arma un **cronograma de cuotas**. En este módulo sigues lo que cada cliente debe, cobras y detectas a quién se le venció el pago.

Para saber cómo se crea una venta a crédito, mira el capítulo **Ventas y Punto de Venta**.

## Pantallas del módulo

| Pantalla | Qué muestra |
|---|---|
| **Ventas a Crédito** | Todas las ventas financiadas, pagadas o con saldo. |
| **Cuotas** | Cada cuota con su vencimiento, monto, pagado y saldo. Las vencidas aparecen como **VENCIDA**. |
| **Cobranza** | Las ventas con saldo pendiente y el **Total por cobrar**. |
| **Morosos** | Los clientes con cuotas vencidas: cuántas, desde cuándo y el **Total adeudado**. |
| **Planes de Pago** | Planes predefinidos (cuotas, frecuencia y recargo) para usar al vender. |
| **Reportes** | Resúmenes de la cartera de créditos. |

## Cobrar una cuota

1. Entra a **Créditos → Cobranza**. Puedes **Filtrar por cliente**.
2. Presiona **Cobrar** en la venta. También puedes abrir la venta desde **Ventas** y usar **Cobrar**.
3. Escribe el monto que paga el cliente y el método (efectivo, QR, transferencia…).
4. Confirma. El pago se aplica a las cuotas más antiguas y, si es en efectivo, entra a tu caja.

> Para cobrar necesitas tener **tu caja abierta**.

En la venta puedes ver el **Cronograma de cuotas** y los **Pagos registrados**, y desde ahí imprimir el comprobante del cobro.

## Controlar la mora

Revisa **Créditos → Morosos** con frecuencia. Ahí ves a quién llamar primero, desde cuándo debe y cuánto. La pantalla **Cuotas** sirve para ver lo que vence en los próximos días.

## Preguntas frecuentes

**El cliente pagó más que una cuota.**
Registra el monto completo. El sistema lo reparte entre las cuotas pendientes.

**Quiero cancelar todo el saldo de una vez.**
Cobra el **Saldo** total de la venta. La venta queda pagada.
