<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Configuración de planes de la plataforma (solo super_admin). Permite crear y
 * editar planes eligiendo módulos y cantidades (usuarios, sucursales, productos)
 * sin tocar el seeder. Los límites vacíos = ilimitado (null).
 */
class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-role:super_admin');
    }

    public function index()
    {
        $plans = Plan::withCount('subscriptions')->ordered()->get();

        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Sube o baja un plan una posición en el listado ($direction: up|down).
     * Intercambia el `sort_order` con el vecino inmediato en el orden actual;
     * si los valores están repetidos o en 0 (planes nuevos), primero normaliza
     * la secuencia a 1..N para que el intercambio siempre tenga efecto.
     */
    public function move(Plan $plan, string $direction)
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $plans = Plan::ordered()->get();

        // Normaliza a 1..N si hay ceros o duplicados (no altera el orden visible).
        if ($plans->pluck('sort_order')->duplicates()->isNotEmpty()
            || $plans->contains(fn (Plan $p) => $p->sort_order < 1)) {
            $plans->each(function (Plan $p, int $i) {
                $p->sort_order = $i + 1;
                $p->saveQuietly();
            });
        }

        $index = $plans->search(fn (Plan $p) => $p->id === $plan->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $plans->count()) {
            return back(); // ya está en el extremo
        }

        $current  = $plans[$index];
        $neighbor = $plans[$target];

        [$current->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $current->sort_order];
        $current->save();
        $neighbor->save();

        return back()->with('success', "Orden actualizado: «{$current->name}».");
    }

    public function create()
    {
        return view('admin.plans.form', ['plan' => new Plan(['features' => [], 'active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Los planes nuevos entran al final del listado.
        $data['sort_order'] = (int) Plan::max('sort_order') + 1;

        Plan::create($data);

        return redirect()->route('plans.index')->with('success', "Plan «{$data['name']}» creado.");
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validateData($request, $plan);

        $plan->update($data);

        return redirect()->route('plans.index')->with('success', "Plan «{$plan->name}» actualizado.");
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: hay empresas suscritas a este plan.']);
        }

        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plan eliminado.');
    }

    /**
     * Valida y normaliza los datos del plan. Genera el slug si viene vacío y
     * convierte los límites vacíos en null (ilimitado).
     */
    protected function validateData(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'description'    => ['nullable', 'string', 'max:1000'],
            'price'          => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', Rule::in(array_keys(Plan::BILLING_PERIODS))],
            'trial_days'     => ['required', 'integer', 'min:0', 'max:365'],
            'max_users'      => ['nullable', 'integer', 'min:1'],
            'max_branches'   => ['nullable', 'integer', 'min:1'],
            'max_products'   => ['nullable', 'integer', 'min:1'],
            'features'       => ['nullable', 'array'],
            'features.*'     => [Rule::in(array_keys(Plan::MODULES))],
            'active'         => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $validated['features'] = array_values($validated['features'] ?? []);
        $validated['active']   = $request->boolean('active');

        return $validated;
    }
}
