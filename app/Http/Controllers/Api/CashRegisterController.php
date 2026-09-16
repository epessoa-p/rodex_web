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
 *
 * Reglas:
 *  - UNA caja por sucursal POR PERSONAL (un personal puede tener varias cajas,
 *    pero en sucursales distintas).
 *  - Una caja con registros (sesiones/movimientos) NO se edita.
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

    /** Sucursales, personal (activos) y combinaciones ya ocupadas. */
    public function formData(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $branches = Branch::where('company_id', $cid)->where('active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (Branch $b) => ['id' => $b->id, 'name' => $b->name])->values();

        $personal = Personal::where('company_id', $cid)->where('active', true)
            ->orderBy('full_name')->get(['id', 'full_name'])
            ->map(fn (Personal $p) => ['id' => $p->id, 'name' => $p->full_name])->values();

        // Pares (sucursal, personal) que ya tienen caja: el móvil los usa para
        // ofrecer solo sucursales libres para el personal elegido.
        $taken = CashRegister::whereNotNull('branch_id')->whereNotNull('assigned_personal_id')
            ->get(['id', 'branch_id', 'assigned_personal_id'])
            ->map(fn (CashRegister $r) => [
                'register_id' => $r->id,
                'branch_id'   => $r->branch_id,
                'personal_id' => $r->assigned_personal_id,
            ])->values();

        return response()->json(['data' => [
            'branches' => $branches,
            'personal' => $personal,
            'taken'    => $taken,
        ]]);
    }

    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $this->validated($request, $cid);

        if ($conflict = $this->conflictResponse($data)) {
            return $conflict;
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

        if ($cashRegister->hasRecords()) {
            return response()->json([
                'message' => "La caja «{$cashRegister->name}» ya tiene sesiones o movimientos registrados y no se puede editar. Crea otra caja si necesitas cambiarla.",
                'code'    => 'register_has_records',
            ], 422);
        }

        $data = $this->validated($request, $cid);

        if ($conflict = $this->conflictResponse($data, $cashRegister->id)) {
            return $conflict;
        }

        $cashRegister->update([
            ...$data,
            'active' => $request->boolean('active', true),
        ]);

        return response()->json(['data' => $this->item($cashRegister->load('branch', 'assignedPersonal'))]);
    }

    /** 422 si ese personal ya tiene caja en esa sucursal; null si está libre. */
    private function conflictResponse(array $data, ?int $exceptId = null)
    {
        $taken = CashRegister::conflict((int) $data['branch_id'], (int) $data['assigned_personal_id'], $exceptId);
        if (! $taken) {
            return null;
        }

        return response()->json([
            'message' => "Ese personal ya tiene la caja «{$taken->name}» en esa sucursal. Solo se permite una caja por sucursal por personal.",
            'code'    => 'personal_already_has_register_in_branch',
        ], 422);
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
            // Con registros la caja queda congelada (no editable).
            'has_records' => $r->hasRecords(),
        ];
    }
}
