---
title: Inventario
icon: bi-box-seam
summary: Registrar productos, controlar el stock por almacén, ajustar precios y revisar el kardex.
module: inventory
permissions: products.view, warehouses.view, inventory.kardex
routes: products.*, inventory.*, warehouses.*, product-categories.*, product-brands.*, product-units.*, product-origins.*, moto-brands.*, moto-models.*, catalog.*
---

## Para qué sirve

Aquí registras los productos que vendes y controlas **cuánto hay en cada almacén**. El stock sube con las compras y baja con las ventas de forma automática.

## Catálogos básicos

Antes de cargar productos conviene tener listos sus catálogos. Puedes crearlos desde su menú o al vuelo, con el botón **+** del formulario del producto:

- **Categorías:** frenos, filtros, lubricantes…;
- **Marcas:** del producto;
- **Unidades:** unidad, juego, litro, par…;
- **Orígenes:** país o procedencia;
- **Marcas y modelos de vehículo:** para indicar con qué vehículos es compatible cada repuesto.

## Registrar un producto

1. Entra a **Inventario → Productos** y presiona **Nuevo**.
2. En **Información básica** completa:
   - el **nombre**, que debe ser claro (por ejemplo: "Pastilla de freno trasera Honda CG 150");
   - **Categoría**, **Marca** y **Unidad**;
   - **SKU**, que si lo dejas vacío se genera solo;
   - **Código** interno y **Código de barras**, que son opcionales;
   - los **modelos de vehículo compatibles**. Así el producto aparece al filtrar por modelo en el POS.
3. En **Precios y stock** completa:
   - **Costo** y **Precio** de venta: el sistema muestra la **Ganancia**;
   - **Stock mínimo**: te avisa cuando el producto está por acabarse;
   - **Ubicación en el local** (por ejemplo: PA5-VI2). Se ve en el POS para encontrar el producto rápido.
4. Si quieres, agrega **Fotos del producto** y guarda.

## Productos con medida (neumáticos, aros, filtros)

Escribe la **medida en el nombre o en el código** del producto, no en la categoría. Por ejemplo:

- "Neumático Bridgestone 205/55 R16";
- "Aro aleación 15 4x100".

Así, en el POS lo encuentras escribiendo solo **205/55** o **R16**. Usa la categoría para el tipo de producto, por ejemplo **Neumáticos auto** o **Aros de aleación**.

> El producto nuevo empieza con stock 0. El stock entra con una **compra** o con un **movimiento de entrada**.

## Ver y corregir el stock

En **Inventario → Inventario** ves la cantidad, el costo, el precio y la ganancia de cada producto.

- Elige un **almacén** para editar las cantidades o la ubicación directamente en la tabla.
- Usa **Exportar inventario** para descargarlo en Excel o PDF.

## Movimientos de almacén

En **Inventario → Almacenes** abre un almacén y usa **Nuevo movimiento**:

| Tipo | Para qué |
|---|---|
| **Entrada** | Ingresa mercadería sin una compra (por ejemplo, el stock inicial). |
| **Salida** | Retira mercadería por uso interno, pérdida o daño. |
| **Transferencia** | Pasa mercadería de un almacén a otro. |
| **Ajuste** | Corrige el stock después de un conteo físico. |

## Subir o bajar precios en bloque

En **Inventario → Inventario** usa **Ajustar precios**:

1. Filtra primero por categoría, marca o búsqueda. El ajuste solo afecta a los productos que estás viendo.
2. Elige **Aumentar** o **Disminuir** y el **Porcentaje**.
3. Elige el redondeo, para no dejar centavos raros.
4. Indica si se aplica al **Precio de venta** y también al costo.
5. Revisa la **Vista previa** (precio actual y precio nuevo) y presiona **Sí, aplicar**.

## Cargar muchos productos desde Excel

Usa **Importar** en Productos o en Inventario:

1. Elige el **Almacén destino**. La columna "Cantidad" fija el stock en ese almacén.
2. Sube el archivo Excel (.xlsx o .xls).
3. El sistema lo **verifica sin guardar**. Revisa y corrige las filas en pantalla.
4. Mira el resumen (unidades y valor) y **confirma**.

## Kardex

**Inventario → Kardex** muestra el historial de cada movimiento: entradas, salidas, ventas, compras y ajustes, con fecha, referencia y usuario. Sirve para responder "¿por qué este producto tiene este stock?".

## Preguntas frecuentes

**Un producto aparece con stock negativo o equivocado.**
Revisa su kardex para encontrar el movimiento incorrecto. Luego corrígelo con un **Ajuste**.

**No puedo cambiar precios.**
Tu rol no tiene el permiso para hacerlo. Pide al administrador que lo revise.
