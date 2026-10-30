---
title: Ventas y Punto de Venta
icon: bi-bag-check
summary: Vender al contado o a crédito desde el POS, cotizar, anular y registrar devoluciones.
module: sales
permissions: pos.access, sales.view, quotes.view, sale-returns.view
routes: pos.*, sales.*, quotes.*, sale-returns.*
---

## Para qué sirve

El **Punto de Venta (POS)** es la pantalla para cobrar rápido en mostrador. Desde **Ventas** consultas lo vendido, imprimes comprobantes, anulas o registras devoluciones.

> **Antes de vender debes tener tu caja abierta.** Si está cerrada, el POS muestra "Caja cerrada" y no deja operar. Mira el capítulo **Caja**.

## Hacer una venta al contado

1. Entra a **Ventas → Punto de Venta**.
2. Busca el producto por **nombre, código o categoría** en el buscador de arriba. También puedes filtrar por **Categoría** o **Modelo** de vehículo.
3. Haz clic en el producto para agregarlo al **Carrito**. Si lo tocas otra vez, sube la cantidad (hasta el stock disponible).
4. En el carrito ajusta la **Cant.** Para quitar un producto usa el ícono de quitar. El precio es el que tiene el producto en Inventario.
5. Elige el **Cliente** o deja **Cliente general**. Si es un cliente nuevo, puedes registrarlo ahí mismo con nombre, documento y teléfono.
6. Si corresponde, aplica un **Descuento**.
7. Elige la **Forma de pago**: Efectivo, QR, Transferencia o Tarjeta, según lo que acepte tu empresa.
8. Presiona **Cobrar**. La venta se registra, el stock baja y el cobro entra a tu caja.

> El botón de **redondeo** baja el total al monto cerrado más cercano (a favor del cliente) para no tener que dar cambio en centavos.

## Vender un producto que no está registrado

Usa el botón de **Venta rápida** junto al buscador. Escribe el nombre del producto, el precio unitario y la cantidad. Sirve para casos puntuales. Lo ideal es registrar el producto en Inventario para controlar su stock.

## Vender a crédito

1. Arma el carrito y elige el cliente. **A crédito no se puede vender a Cliente general.**
2. Presiona **A Crédito**.
3. Completa el plan de pagos:
   - **N° cuotas** y **Cada (días)**: por ejemplo, 4 cuotas cada 30 días;
   - **Primera fecha** de pago;
   - **Pago inicial**, si el cliente deja algo en ese momento;
   - **% adicional (recargo)**, si cobras interés.
4. Revisa el cronograma de vencimientos y montos, y confirma.

El saldo queda en **Créditos → Cobranza**. Mira el capítulo **Créditos y cobranza**.

## Ver, imprimir o anular una venta

1. Entra a **Ventas → Ventas** y abre la venta.
2. Desde el comprobante puedes:
   - **Imprimir** el comprobante;
   - **Cobrar** el saldo, si la venta tiene saldo pendiente;
   - **Devolver** productos (ver más abajo);
   - **Anular** la venta. Esto no se puede deshacer: los productos vuelven al stock.

## Cotizaciones

- **Punto de Cotización:** funciona igual que el POS, pero no cobra ni descuenta stock. Sirve para entregar un presupuesto al cliente.
- **Cotizaciones:** lista de presupuestos emitidos. Cuando el cliente acepta, abre la cotización y usa **Convertir en venta**.

## Devoluciones

1. Abre la venta y presiona **Devolver** (o entra a **Ventas → Devoluciones**).
2. Escribe el **Motivo** (por ejemplo: producto defectuoso o error en el pedido).
3. En **Productos a devolver**, indica la **Cant. a devolver** de cada uno. El sistema no deja devolver más de lo vendido.
4. Revisa el **Total a reembolsar** y confirma. Los productos vuelven al stock.

## Preguntas frecuentes

**El POS dice "Caja cerrada".**
Abre tu caja desde el botón **Abrir caja** de la barra superior.

**No aparece un producto en el buscador.**
Puede estar inactivo o sin stock en tu almacén. Revísalo en **Inventario → Productos**.

**Me equivoqué en una venta ya cobrada.**
Si es toda la venta, **anúlala**. Si es parte, haz una **devolución**.
