# Sistema de Cajas — Diseño y Modelo de Negocio

Documento técnico para replicar la arquitectura de cajas en otro sistema (ej. tienda de repuestos de motos).

---

## Concepto general

El sistema de cajas controla **quién maneja dinero, cuándo y en qué sucursal**.
Cada operación que mueve dinero (venta, compra, pago, devolución) queda vinculada a
una **sesión de caja abierta**. Sin sesión abierta no se puede operar.

---

## Entidades y relaciones

```
Company
  └── Branch (sucursal)
        └── CashRegister (caja física)
              ├── Personal (cajero asignado) ──── User (cuenta de acceso)
              └── CashRegisterSession (apertura/cierre)
                    └── CashMovement[] (ingresos y egresos)
```

### CashRegister — La caja física

Representa una caja registradora en una sucursal. Es un objeto **permanente y configurable** por el administrador, no por el cajero.

| Campo | Descripción |
|-------|-------------|
| `company_id` | Empresa propietaria |
| `branch_id` | Sucursal donde está físicamente |
| `name` | Nombre descriptivo ("Caja Principal", "Caja 2") |
| `description` | Notas opcionales |
| `assigned_personal_id` | Personal (empleado) asignado como cajero |
| `active` | Si está habilitada |

**Regla de negocio clave:** un personal solo puede tener **una caja asignada por sucursal**.

---

### CashRegisterSession — La apertura de caja

Cada vez que el cajero inicia su turno, **crea una sesión**. Cuando termina, la **cierra**.
Todos los movimientos de dinero se asocian a la sesión activa.

| Campo | Descripción |
|-------|-------------|
| `cash_register_id` | A qué caja pertenece |
| `opened_by` | ID del User que abrió |
| `closed_by` | ID del User que cerró (null si sigue abierta) |
| `opening_amount` | Monto inicial declarado al abrir |
| `closing_amount` | Monto físico contado al cerrar |
| `closing_breakdown` | JSON con conteo de billetes/monedas por denominación |
| `opening_notes` | Observación al abrir |
| `closing_notes` | Observación al cerrar |
| `opened_at` | Timestamp de apertura |
| `closed_at` | Timestamp de cierre (null = sesión abierta) |

**Caja abierta** = sesión con `closed_at IS NULL`.

**Denominaciones del breakdown** (adaptables por país/moneda):
```json
{
  "quantities": { "b200": 2, "b100": 5, "b50": 0, "b20": 3, "b10": 1,
                  "m5": 4,   "m2": 2,  "m1": 7,  "c50": 0, "c20": 3, "c10": 0 },
  "total": 734.60
}
```

---

### CashMovement — Cada ingreso o egreso

Registro atómico de **cada transacción de dinero** dentro de una sesión.
No se eliminan — son el libro contable de la caja.

| Campo | Descripción |
|-------|-------------|
| `company_id` | Empresa |
| `cash_register_id` | Caja |
| `cash_register_session_id` | Sesión activa al momento del movimiento |
| `user_id` | Usuario que generó el movimiento |
| `type` | `income` (ingreso) o `expense` (egreso) |
| `category` | Categoría del movimiento (ver tabla abajo) |
| `amount` | Monto positivo siempre; el `type` define dirección |
| `reference_type` | Modelo PHP al que refiere (Polymorphic) |
| `reference_id` | ID del registro de ese modelo |
| `description` | Texto legible para el usuario |
| `movement_date` | Fecha/hora del movimiento |

#### Categorías de movimiento — sistema actual (préstamos)

| category | type | Descripción |
|----------|------|-------------|
| `loan_disbursement` | expense | Entrega de dinero al cliente al crear préstamo |
| `loan_payment` | income | Pago de capital del préstamo |
| `interest_payment` | income | Pago de interés periódico |
| `amortization` | income | Pago de amortización |
| `liquidation` | income | Liquidación total del préstamo |
| `purchase` | expense | Compra de artículo al cliente |
| `sale` | income | Venta de artículo al cliente |
| `sale_installment` | income | Cuota de venta en cuotas |
| `sale_down_payment` | income | Cuota inicial de venta |
| `sale_liquidation` | income | Pago final de venta en cuotas |
| `sale_return` | expense | Devolución de venta |

#### Categorías sugeridas — tienda de repuestos de motos

| category | type | Descripción |
|----------|------|-------------|
| `sale` | income | Venta de repuesto / accesorios |
| `sale_return` | expense | Devolución de venta |
| `purchase_supplier` | expense | Compra a proveedor (si pasa por caja) |
| `expense_operational` | expense | Gasto operativo (limpieza, luz, etc.) |
| `expense_supplier` | expense | Pago a proveedor |
| `advance_customer` | income | Anticipo de pedido |
| `advance_return` | expense | Devolución de anticipo |
| `cash_adjustment_in` | income | Ajuste manual positivo (diferencia de caja) |
| `cash_adjustment_out` | expense | Ajuste manual negativo |

---

## Flujo de operación

### Apertura de caja

```
Cajero llega → Botón "Aperturar caja"
  → Ingresa monto inicial en efectivo
  → Sistema crea CashRegisterSession(opened_at=now, closed_at=null)
  → Caja queda en estado ABIERTA
  → El cajero puede operar (crear ventas, recibir pagos, etc.)
```

### Durante el turno

```
Cada operación de dinero →
  sistema crea automáticamente un CashMovement vinculado a la sesión activa
  sin intervención manual del cajero
```

### Cierre de caja

```
Cajero termina turno → Botón "Cerrar caja"
  → Cuenta físicamente el dinero en caja
  → Ingresa monto total O cuenta denominación por denominación
  → Sistema registra closing_amount y closing_breakdown
  → Actualiza la sesión con closed_at=now
  → El sistema puede calcular: diferencia = closing_amount - (opening_amount + ingresos - egresos)
  → Caja queda CERRADA hasta la próxima apertura
```

---

## Reglas de negocio críticas

| Regla | Descripción |
|-------|-------------|
| Sin caja abierta = sin operaciones | Cualquier acción que mueva dinero verifica primero que haya una sesión abierta asignada al usuario |
| Una sesión abierta por caja | No se puede aperturar una caja que ya tiene sesión activa |
| El cajero ve solo su caja | El sistema resuelve la caja del usuario por `Personal.user_id → CashRegister.assigned_personal_id` |
| Gerente puede elegir caja | Si el personal tiene `cargo_id = 1` (gerente), puede seleccionar cuál de sus cajas abrir |
| Sucursal derivada de la caja | Al registrar cualquier operación, la sucursal NO la elige el usuario — la hereda de la caja abierta |
| Movimientos son inmutables | Los `CashMovement` no se editan ni eliminan; errores se corrigen con movimientos contrarios |

---

## Cómo el sistema resuelve la caja del usuario

```php
// Paso 1: encontrar el registro Personal del User
$personal = Personal::where('user_id', $userId)->first();

// Paso 2: obtener IDs de todas sus cajas activas asignadas
$registerIds = CashRegister::where('assigned_personal_id', $personal->id)
    ->where('company_id', $companyId)
    ->where('active', true)
    ->pluck('id');

// Paso 3: buscar si alguna tiene sesión abierta
$session = CashRegisterSession::whereIn('cash_register_id', $registerIds)
    ->whereNull('closed_at')
    ->latest()
    ->first();

// $session === null → el usuario no tiene caja abierta → bloquear operaciones
```

---

## Esquema SQL

```sql
-- Cajas físicas
CREATE TABLE cash_registers (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id           BIGINT UNSIGNED NOT NULL,
    branch_id            BIGINT UNSIGNED NOT NULL,
    name                 VARCHAR(255) NOT NULL,
    description          VARCHAR(500) NULL,
    assigned_personal_id BIGINT UNSIGNED NULL,
    active               TINYINT(1) DEFAULT 1,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    deleted_at           TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (assigned_personal_id) REFERENCES personals(id)
);

-- Sesiones (aperturas/cierres)
CREATE TABLE cash_register_sessions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_register_id    BIGINT UNSIGNED NOT NULL,
    opened_by           BIGINT UNSIGNED NOT NULL,
    closed_by           BIGINT UNSIGNED NULL,
    opening_amount      DECIMAL(15,2) NOT NULL DEFAULT 0,
    closing_amount      DECIMAL(15,2) NULL,
    closing_breakdown   JSON NULL,
    opening_notes       VARCHAR(500) NULL,
    closing_notes       VARCHAR(500) NULL,
    opened_at           TIMESTAMP NOT NULL,
    closed_at           TIMESTAMP NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id),
    FOREIGN KEY (opened_by) REFERENCES users(id),
    FOREIGN KEY (closed_by) REFERENCES users(id),
    INDEX idx_session_open (cash_register_id, closed_at)
);

-- Movimientos de caja
CREATE TABLE cash_movements (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id                BIGINT UNSIGNED NOT NULL,
    cash_register_id          BIGINT UNSIGNED NOT NULL,
    cash_register_session_id  BIGINT UNSIGNED NOT NULL,
    user_id                   BIGINT UNSIGNED NOT NULL,
    type                      ENUM('income','expense') NOT NULL,
    category                  VARCHAR(60) NOT NULL,
    amount                    DECIMAL(15,2) NOT NULL,
    reference_type            VARCHAR(255) NULL,  -- nombre del modelo (ej: App\Models\Sale)
    reference_id              BIGINT UNSIGNED NULL,
    description               VARCHAR(500) NULL,
    movement_date             TIMESTAMP NOT NULL,
    created_at                TIMESTAMP NULL,
    updated_at                TIMESTAMP NULL,
    FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id),
    FOREIGN KEY (cash_register_session_id) REFERENCES cash_register_sessions(id),
    INDEX idx_movement_session (cash_register_session_id),
    INDEX idx_movement_company_date (company_id, movement_date)
);
```

---

## Reporte de cierre

Al cerrar una sesión se puede calcular:

| Concepto | Cálculo |
|----------|---------|
| Total ingresos | `SUM(amount) WHERE type='income'` en esa sesión |
| Total egresos | `SUM(amount) WHERE type='expense'` en esa sesión |
| Saldo esperado | `opening_amount + ingresos - egresos` |
| Saldo físico | `closing_amount` |
| Diferencia | `closing_amount - saldo_esperado` (positivo = sobrante, negativo = faltante) |

---

## Adaptación para tienda de repuestos de motos

### Diferencias respecto al sistema de préstamos

| Aspecto | Préstamos | Repuestos |
|---------|-----------|-----------|
| Operación principal | Desembolso + cobro cuotas | Venta de productos |
| Ingreso típico | Pago de préstamo | Venta de repuesto |
| Egreso típico | Desembolso de préstamo | Compra a proveedor / gastos |
| Referencia del movimiento | `Loan`, `LoanPayment` | `Sale`, `Purchase`, `Expense` |
| Movimiento manual | Raro | Frecuente (gastos operativos) |

### Lo que se mantiene igual

- Toda la estructura de `CashRegister`, `CashRegisterSession`, `CashMovement`
- El flujo de apertura y cierre
- La regla de "sin caja abierta, sin operaciones"
- La vinculación `Personal → CashRegister → Session → Movement`
- El reporte de cierre con diferencia

### Categorías a agregar / modificar

Reemplazar las categorías de préstamo por las de repuestos (ver tabla de categorías sugeridas arriba).
Agregar soporte para **movimientos manuales** (gastos operativos) que el cajero registra directamente,
sin que los genere una venta.

---

## UI — Botón de caja en la barra de navegación

Además de la funcionalidad backend, se necesita un **botón de apertura/cierre visible** en la barra
de navegación, al lado del botón de perfil del usuario.

### Comportamiento esperado

```
Caja CERRADA:
  [● Abrir caja]  [avatar/nombre]

Caja ABIERTA:
  [● Caja: Sucursal Centro ▼]  [avatar/nombre]
       └─ Ver movimientos
       └─ Cerrar caja
```

### Lógica del botón

- Al cargar cualquier página, el sistema consulta si el usuario tiene sesión activa.
- Si `closed_at IS NULL` → mostrar badge verde con nombre de sucursal y dropdown para cerrar.
- Si no hay sesión → mostrar botón rojo/naranja "Abrir caja" que abre modal de apertura.
- El modal de apertura pide: **monto inicial** y **notas opcionales**.
  - Si el usuario es gerente: también muestra selector de caja (si tiene más de una asignada).
- El modal de cierre pide: **conteo de denominaciones** (o monto total) y **notas opcionales**.
  - Muestra el resumen: ingresos, egresos y diferencia calculada antes de confirmar.

### Posición en el navbar

```html
<!-- Navbar right side -->
<div class="d-flex align-items-center gap-2">
    <!-- Botón de caja -->
    @include('partials.cash-register-btn')

    <!-- Perfil de usuario (existente) -->
    @include('partials.user-menu')
</div>
```

### Estado del botón en la app móvil

En la app móvil, usar el campo `cash_register` del login / `GET /auth/me`:
- `cash_register === null` → mostrar banner/aviso "Sin caja abierta"
- `cash_register.is_open === true` → mostrar nombre de sucursal en el header como indicador

---

## Resumen de archivos del sistema actual

| Archivo | Rol |
|---------|-----|
| `app/Models/CashRegister.php` | Modelo de caja, relación con branch y personal |
| `app/Models/CashRegisterSession.php` | Modelo de sesión (apertura/cierre) |
| `app/Models/CashMovement.php` | Modelo de movimiento individual |
| `app/Models/Personal.php` | Empleado con `user_id`, `branch_id`, `cargo_id` |
| `app/Http/Controllers/Admin/CashRegisterController.php` | CRUD de cajas + apertura/cierre |
| `app/Http/Controllers/Api/AuthController.php` | Retorna `cash_register` en login y `/me` |
| `app/Http/Controllers/Reports/ReportController.php` | Reporte de movimientos y cierres |
