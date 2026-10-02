<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CashRegisterSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'personal_id',
        'opened_by',
        'closed_by',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'status',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount'  => 'decimal:2',
        'closing_amount'  => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference'      => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** Totales generales (todos los medios): para los resúmenes. */
    public function totalIncome(): float
    {
        return (float) $this->movements()->where('type', 'income')->sum('amount');
    }

    public function totalExpense(): float
    {
        return (float) $this->movements()->where('type', 'expense')->sum('amount');
    }

    // ── Efectivo vs. otros medios ─────────────────────────────────────────
    //
    // Lo cobrado por QR/transferencia/tarjeta queda registrado en la sesión,
    // pero NO está en el cajón: el esperado al cerrar es solo el efectivo, y
    // los otros medios se informan aparte.

    private ?array $breakdownCache = null;

    /**
     * Reparto de los movimientos entre efectivo y otros medios. Pura (sin BD):
     * recibe filas {type, method, total} y el monto de apertura.
     *
     * @param  iterable<object|array>  $rows
     * @return array{cash_income: float, cash_expense: float, expected_cash: float,
     *               other: array<int, array{method: string, label: string, income: float, expense: float, net: float}>}
     */
    public static function breakdown(iterable $rows, float $opening): array
    {
        $cashIn = 0.0;
        $cashOut = 0.0;
        $other = [];

        foreach ($rows as $r) {
            $r      = (array) $r;
            $amount = (float) ($r['total'] ?? $r['amount'] ?? 0);
            $method = $r['method'] ?? null;
            $in     = ($r['type'] ?? '') === 'income';

            if (CashMovement::isCash($method)) {
                $in ? $cashIn += $amount : $cashOut += $amount;
                continue;
            }
            $other[$method] ??= ['method' => $method, 'label' => CashMovement::shortLabel($method), 'income' => 0.0, 'expense' => 0.0];
            $in ? $other[$method]['income'] += $amount : $other[$method]['expense'] += $amount;
        }

        // Orden estable: el de las formas de cobro, luego cualquier otro.
        $order = array_flip(CashMovement::SALE_METHODS);
        uksort($other, fn ($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99) ?: strcmp($a, $b));

        return [
            'cash_income'   => round($cashIn, 2),
            'cash_expense'  => round($cashOut, 2),
            'expected_cash' => round($opening + $cashIn - $cashOut, 2),
            'other'         => array_values(array_map(fn ($o) => $o + [
                'net' => round($o['income'] - $o['expense'], 2),
            ], array_map(fn ($o) => [
                'method'  => $o['method'],
                'label'   => $o['label'],
                'income'  => round($o['income'], 2),
                'expense' => round($o['expense'], 2),
            ], $other))),
        ];
    }

    /** Reparto de ESTA sesión (una consulta agrupada, memorizada). */
    public function methodBreakdown(): array
    {
        return $this->breakdownCache ??= self::breakdown(
            $this->movements()
                ->selectRaw('type, method, SUM(amount) as total')
                ->groupBy('type', 'method')
                ->get()
                ->map(fn ($r) => ['type' => $r->type, 'method' => $r->method, 'total' => $r->total]),
            (float) $this->opening_amount
        );
    }

    public function forgetBreakdown(): void
    {
        $this->breakdownCache = null;
    }

    public function cashIncome(): float
    {
        return $this->methodBreakdown()['cash_income'];
    }

    public function cashExpense(): float
    {
        return $this->methodBreakdown()['cash_expense'];
    }

    /** Otros medios con movimiento (QR, transferencia…): [{method, label, income, expense, net}]. */
    public function otherMethods(): array
    {
        return $this->methodBreakdown()['other'];
    }

    /** Lo que debe haber en el CAJÓN: apertura + ingresos en efectivo − egresos en efectivo. */
    public function expectedBalance(): float
    {
        return $this->methodBreakdown()['expected_cash'];
    }

    /** ¿La empresa deja pagar gastos con lo cobrado por otros medios? */
    public function usesOtherMethodsForOutflows(): bool
    {
        return $this->cashRegister?->company?->expensesUseOtherMethods() ?? true;
    }

    /**
     * Disponible para pagar desde la caja: el efectivo y, si la empresa lo
     * permite, además lo que queda de QR/transferencia/tarjeta.
     */
    public function availableForOutflow(): float
    {
        return self::availableFrom($this->methodBreakdown(), $this->usesOtherMethodsForOutflows());
    }

    public static function availableFrom(array $breakdown, bool $useOthers): float
    {
        $total = (float) $breakdown['expected_cash'];
        if ($useOthers) {
            foreach ($breakdown['other'] as $o) {
                $total += max(0, (float) $o['net']);
            }
        }

        return round(max(0, $total), 2);
    }

    /**
     * Reparte una salida de dinero: efectivo primero y lo que falte con los
     * otros medios (QR, transferencia, tarjeta), según lo que quede de cada uno.
     * Sin permiso para usar otros medios, todo sale del efectivo. Si ni así
     * alcanza, el resto se anota en efectivo (el llamador valida el tope).
     *
     * @return array<int, array{method: string, amount: float}>
     */
    public static function allocate(float $amount, array $breakdown, bool $useOthers): array
    {
        $amount = round($amount, 2);
        if (! $useOthers) {
            return [['method' => 'efectivo', 'amount' => $amount]];
        }

        $pieces = [];
        $rest   = $amount;

        $cash = min($rest, max(0, (float) $breakdown['expected_cash']));
        if ($cash > 0) {
            $pieces[] = ['method' => 'efectivo', 'amount' => round($cash, 2)];
            $rest = round($rest - $cash, 2);
        }
        foreach ($breakdown['other'] as $o) {
            if ($rest <= 0) {
                break;
            }
            $take = min($rest, max(0, (float) $o['net']));
            if ($take > 0) {
                $pieces[] = ['method' => $o['method'], 'amount' => round($take, 2)];
                $rest = round($rest - $take, 2);
            }
        }
        if ($rest > 0) {
            // No alcanza con nada: el faltante queda en efectivo (se verá en el cierre).
            if (isset($pieces[0]) && $pieces[0]['method'] === 'efectivo') {
                $pieces[0]['amount'] = round($pieces[0]['amount'] + $rest, 2);
            } else {
                array_unshift($pieces, ['method' => 'efectivo', 'amount' => $rest]);
            }
        }

        return $pieces;
    }

    public function allocateOutflow(float $amount): array
    {
        return self::allocate($amount, $this->methodBreakdown(), $this->usesOtherMethodsForOutflows());
    }

    /**
     * Registra una SALIDA de la caja repartida por medio (ver allocate()): un
     * CashMovement por parte, con la misma categoría, referencia y descripción.
     * Si el llamador indica un método no-efectivo explícito, se respeta tal cual.
     *
     * @param  array  $attrs  atributos del CashMovement (sin type/amount/method)
     * @return Collection<int, CashMovement>
     */
    public function recordOutflow(array $attrs, float $amount, ?string $method = null): Collection
    {
        $pieces = ($method !== null && ! CashMovement::isCash($method))
            ? [['method' => $method, 'amount' => round($amount, 2)]]
            : $this->allocateOutflow($amount);

        $split   = count($pieces) > 1;
        $created = collect();
        foreach ($pieces as $p) {
            $desc = $attrs['description'] ?? null;
            if ($split && ! CashMovement::isCash($p['method'])) {
                $desc = trim(($desc ?? '') . ' (parte ' . CashMovement::shortLabel($p['method']) . ')');
            }
            $created->push(CashMovement::create(array_merge($attrs, [
                'cash_register_session_id' => $this->id,
                'cash_register_id'         => $attrs['cash_register_id'] ?? $this->cash_register_id,
                'type'                     => 'expense',
                'amount'                   => $p['amount'],
                'method'                   => $p['method'],
                'description'              => $desc,
            ])));
        }
        $this->forgetBreakdown();

        return $created;
    }

    /**
     * "Efectivo Bs 100 · QR Bs 100" cuando el disponible incluye otros medios
     * (y la empresa lo permite); null si todo es efectivo. Moneda de la empresa.
     */
    public function availabilityBreakdownText(): ?string
    {
        if (! $this->usesOtherMethodsForOutflows()) {
            return null;
        }
        $b      = $this->methodBreakdown();
        $others = array_filter($b['other'], fn ($o) => $o['net'] > 0.004);
        if (! $others) {
            return null;
        }
        $company = $this->cashRegister?->company;
        $parts   = ['Efectivo ' . money(max(0, $b['expected_cash']), $company)];
        foreach ($others as $o) {
            $parts[] = $o['label'] . ' ' . money($o['net'], $company);
        }

        return implode(' · ', $parts);
    }

    /** Para respuestas y vistas: [{method, label, amount}] de los otros medios con saldo. */
    public function otherMethodsSummary(): array
    {
        return array_values(array_map(fn ($o) => [
            'method' => $o['method'],
            'label'  => $o['label'],
            'amount' => $o['net'],
        ], array_filter($this->otherMethods(), fn ($o) => abs($o['net']) >= 0.005 || $o['income'] > 0)));
    }
}
