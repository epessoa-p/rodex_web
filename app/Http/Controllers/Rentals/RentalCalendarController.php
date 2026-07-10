<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\Motos\MotoUnit;
use App\Models\Rentals\RentalContract;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RentalCalendarController extends Controller
{
    public function index()
    {
        return view('rentals.calendar.index');
    }

    public function data(Request $request)
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;

        // Grupos = unidades de moto (que no estén anuladas/vendidas)
        $units = MotoUnit::query()
            ->with('model.brand')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereNotIn('status', ['anulada', 'vendida'])
            ->get();

        $groups = $units->map(fn ($u) => [
            'id'      => $u->id,
            'content' => e($u->display_name),
        ])->values();

        // Items = contratos activos/en rango
        $contracts = RentalContract::query()
            ->with('client')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['reservada', 'contrato', 'entregada', 'devuelta', 'cerrada'])
            ->get();

        $colorMap = [
            'reservada' => '#0dcaf0',
            'contrato'  => '#0d6efd',
            'entregada' => '#ffc107',
            'devuelta'  => '#6c757d',
            'cerrada'   => '#198754',
        ];

        $items = $contracts->map(function ($c) use ($colorMap) {
            $color = $colorMap[$c->status] ?? '#6c757d';
            $client = $c->client?->full_name ?? 'Cliente';
            // vis-timeline end es exclusivo: sumamos 1 día para incluir end_date completo
            $end = Carbon::parse($c->end_date)->addDay()->toDateString();
            return [
                'id'         => $c->id,
                'group'      => $c->moto_unit_id,
                'start'      => Carbon::parse($c->start_date)->toDateString(),
                'end'        => $end,
                'content'    => e($c->code . ' · ' . $client),
                'title'      => e($c->code . ' — ' . $client . ' (' . $c->status_label . ')'),
                'style'      => "background-color:{$color};border-color:{$color};color:#fff;",
                'rentalId'   => $c->id,
            ];
        })->values();

        return response()->json([
            'groups' => $groups,
            'items'  => $items,
        ]);
    }
}
