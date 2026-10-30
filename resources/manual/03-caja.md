---
title: Caja
icon: bi-safe2
summary: Abrir y cerrar la caja del día, registrar gastos y revisar los movimientos.
module:
permissions:
routes: cash.*, cash-registers.*
---

## Para qué sirve

La caja controla el **dinero del día**: con cuánto abres, cuánto entra por ventas y cobros, cuánto sale por gastos o pagos, y cuánto debe haber al cerrar.

Cada cajero tiene su caja asignada en una sucursal (**Administración → Cajas**). Sin una caja asignada no puedes cobrar.

## Abrir la caja

1. En la barra superior presiona **Abrir caja**.
2. Elige la caja (si tienes más de una).
3. Escribe el **Monto inicial**: el efectivo con el que empiezas, para dar cambio.
4. Si hace falta, agrega **Notas de apertura** y presiona **Aperturar**.

El botón cambia a **Caja: (sucursal)** con un punto verde. Ya puedes vender.

## Durante el día

Todo lo que cobras en el POS, los cobros de créditos y los pagos que haces desde caja se registran solos. Para verlos, abre el menú de tu caja (barra superior) y elige **Ver movimientos**.

## Registrar un gasto

Usa el botón **Registrar gasto** de la barra superior.

1. Elige el **Tipo de gasto**:
   - **Servicio (básico / externo):** luz, agua, internet…;
   - **Pago a personal:** sueldo o adelanto. Indica la persona y el período (día, semana, quincena o mes);
   - **Transporte / envío**;
   - **Pago a proveedor:** abono a una factura pendiente de compra;
   - **Otro gasto operativo**.
2. Escribe el **Concepto**, el **Monto** y el **Método** de pago.
3. Presiona **Registrar gasto**.

> Si el gasto se paga en efectivo, el dinero sale del cajón y baja lo que debe haber al cierre.

## Cerrar la caja

1. Abre el menú de tu caja (barra superior) y elige **Cerrar caja**.
2. El sistema muestra:
   - **Monto inicial**;
   - **Ingresos efectivo** y **Egresos efectivo**;
   - **Efectivo que debe haber en el cajón**.
3. Cuenta el dinero y escribe el **Monto físico contado**.
4. Si hay diferencia, explica el motivo en **Notas** y confirma.

> Solo se cuenta el **efectivo**. Lo cobrado por QR, transferencia o tarjeta no está en el cajón y se informa aparte.

## Revisar cierres anteriores

En **Administración → Cajas** abre una caja para ver su **Historial de sesiones**: apertura, cajero, monto inicial, ingresos, egresos, monto de cierre y **Diferencia**.

- Una diferencia negativa es un **faltante**.
- Una diferencia positiva es un **sobrante**.

En **Administración → Movimientos** ves todos los ingresos y egresos de caja, con filtros.

## Preguntas frecuentes

**No me aparece el botón Abrir caja.**
Tu usuario no tiene una caja asignada. Pide al administrador que te asigne una en **Administración → Cajas**.

**Cerré la caja con un monto equivocado.**
El cierre queda registrado con la diferencia. Informa al encargado para que lo revise en el historial de la caja.
