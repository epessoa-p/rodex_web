<?php

namespace App\Http\Controllers;

use App\Support\UserManual;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Manual de usuario (resources/manual/*.md). Cada usuario ve solo los
 * capítulos de los módulos que tiene en el menú; el PDF respeta lo mismo.
 */
class ManualController extends Controller
{
    public function index()
    {
        return view('manual.index', ['chapters' => $this->visible()]);
    }

    public function show(string $slug)
    {
        $chapters = $this->visible();
        abort_unless(isset($chapters[$slug]), 404);

        $slugs    = array_keys($chapters);
        $position = array_search($slug, $slugs, true);
        $company  = auth()->user()->getCurrentCompany();

        return view('manual.show', [
            'chapters' => $chapters,
            'chapter'  => $chapters[$slug],
            'content'  => UserManual::render($chapters[$slug], $company),
            'prev'     => $position > 0 ? $chapters[$slugs[$position - 1]] : null,
            'next'     => $chapters[$slugs[$position + 1] ?? ''] ?? null,
        ]);
    }

    /** PDF de un capítulo, o del manual completo si no se indica. */
    public function pdf(?string $slug = null)
    {
        $chapters = $this->visible();
        if ($slug !== null) {
            abort_unless(isset($chapters[$slug]), 404);
            $chapters = [$slug => $chapters[$slug]];
        }

        $company = auth()->user()->getCurrentCompany();
        $rendered = array_map(fn ($c) => $c + ['content' => UserManual::render($c, $company)], $chapters);

        $pdf = Pdf::loadView('manual.pdf', [
            'chapters'    => $rendered,
            'single'      => $slug !== null,
            'companyName' => $company?->name ?? config('brand.name'),
            'companyLogo' => $company?->logo_file ?? (is_file(public_path(config('brand.logo'))) ? public_path(config('brand.logo')) : null),
            'generatedAt' => now()->format('d/m/Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('manual_' . ($slug ?? 'completo') . '.pdf');
    }

    private function visible(): array
    {
        $user = auth()->user();

        return UserManual::visibleFor($user, $user->getCurrentCompany());
    }
}
