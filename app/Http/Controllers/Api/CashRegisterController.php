<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gestión de cajas desde el móvil: crear una caja y asignarla a un personal
 * (requisito para que ese personal pueda abrir caja y vender). Alcance admin.
 */
class CashRegisterController extends Controller
{
    /** Cajas de la empresa (con sucursal y personal asignado). */
    public function index()
    {
        $registers = CashRegister::with(['branch:id,name', 'assignedPersonal:id,full_name'])
            ->orderBy('name')->get()
            ->map(fn (CashRegister $r) => $this->item($r));

        return response()->json(['data' => $registers]);
    }

    /** Sucursales y personal (activos) para el formulario de caja. */
    public function formData(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        // `register_id` = caja que ya ocupa la sucursal (null = libre). El móvil
        // lo usa para ofrecer solo sucursales sin caja al crear (una por sucursal).
        $taken = CashRegister::whereNotNull('branch_id')
            ->pluck('id', 'branch_id');

        $branches = Branch::where('company_id', $cid)->where('active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (Branch $b) => [
                'id'          => $b->id,
                'name'        => $b->name,
                'register_id' => $taken[$b->id] ?? null,
            ])->values();

        $personal = Personal::where('company_id', $cid)->where('active', true)
            ->orderBy('full_name')->get(['id', 'full_name'])
            ->map(fn (Personal $p) => ['id' => $p->id, 'name' => $p->full_name])->values();

        return response()->json(['data' => ['branches' => $branches, 'personal' => $personal]]);
    }

    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $this->validated($request, $cid);

        if ($taken = $this->registerInBranch($data['branch_id'])) {
            return response()->json([
                'message' => "Esa sucursal ya tiene la caja «{$taken->name}». Solo se permite una caja por sucursal.",
                'code'    => 'branch_already_has_register',
            ], 422);
        }

        $register = CashRegister::create([
            ...$data,
            'company_id' => $cid,
            'active'     => $request->boolean('active', true),
            'created_by' => auth()->id(),
        ]);

        return response()->json(['data' => $this->item($register->load('branch', 'assignedPersonal'))], 201);
    }

    public function update(Request $request, CashRegister $cashRegister)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $this->validated($request, $cid);

        // Al MOVER la caja a otra sucursal, esa sucursal debe estar libre. Si se
        // queda en la suya, se permite: hay empresas con varias cajas por sucursal
        // creadas antes de esta regla y deben poder seguir editándose.
        if ((int) $data['branch_id'] !== (int) $cashRegister->branch_id
            && ($taken = $this->registerInBranch($data['branch_id'], $cashRegister->id))) {
            return response()->json([
                'message' => "Esa sucursal ya tiene la caja «{$taken->name}». Solo se permite una caja por sucursal.",
                'code'    => 'branch_already_has_register',
            ], 422);
        }

        $cashRegister->update([
            ...$data,
            'active' => $request->boolean('active', true),
        ]);

        return response()->json(['data' => $this->item($cashRegister->load('branch', 'assignedPersonal'))]);
    }

    /**
     * Caja existente en una sucursal (excluyendo opcionalmente una), o null.
     * Regla: una caja por sucursal. El global scope aísla por empresa y
     * SoftDeletes excluye las eliminadas, así que una caja borrada libera la sucursal.
     */
    private function registerInBranch(int $branchId, ?int $exceptId = null): ?CashRegister
    {
        return CashRegister::where('branch_id', $branchId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->first();
    }

    private function validated(Request $request, ?int $cid): array
    {
        return $request->validate([
            'branch_id'            => ['required', Rule::exists('branches', 'id')->where('company_id', $cid)],
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:500'],
            'assigned_personal_id' => ['required', Rule::exists('personals', 'id')->where('company_id', $cid)],
            'active'               => ['sometimes', 'boolean'],
        ]);
    }

    private function item(CashRegister $r): array
    {
        return [
            'id'          => $r->id,
            'name'        => $r->name,
            'description' => $r->description,
            'branch'      => $r->branch?->name,
            'branch_id'   => $r->branch_id,
            'personal'    => $r->assignedPersonal?->full_name,
            'assigned_personal_id' => $r->assigned_personal_id,
            'active'      => (bool) $r->active,
            'has_session' => (bool) $r->activeSession(),
        ];
    }
}
