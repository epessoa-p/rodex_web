<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Manual de usuario: un capítulo por archivo Markdown en resources/manual.
 *
 * Cada archivo empieza con una cabecera simple:
 *   ---
 *   title: Ventas y Punto de Venta
 *   icon: bi-bag-check
 *   summary: Vender al contado o a crédito…
 *   module: sales                  (feature del plan; vacío = siempre)
 *   permissions: pos.access, sales.view   (basta una; vacío = todos)
 *   routes: pos.*, sales.*         (pantallas donde el botón "?" abre este capítulo)
 *   ---
 *
 * En el texto, {moneda} y {empresa} se reemplazan por los de la empresa activa
 * (nunca se escribe "Bs" fijo).
 */
class UserManual
{
    private static ?array $chapters = null;

    /** Todos los capítulos, ordenados por nombre de archivo (01-, 02-…). */
    public static function chapters(): array
    {
        if (self::$chapters !== null) {
            return self::$chapters;
        }

        $files = glob(resource_path('manual/*.md')) ?: [];
        sort($files);

        $chapters = [];
        foreach ($files as $file) {
            $chapter = self::parse($file);
            $chapters[$chapter['slug']] = $chapter;
        }

        return self::$chapters = $chapters;
    }

    /** Capítulos que el usuario puede ver: mismo criterio que el menú lateral. */
    public static function visibleFor(?User $user, ?Company $company): array
    {
        if (! $user) {
            return [];
        }

        return array_filter(self::chapters(), function (array $c) use ($user, $company) {
            if ($user->is_super_admin) {
                return true;
            }
            if ($c['module'] && ! $company?->planAllows($c['module'])) {
                return false;
            }
            if (! $c['permissions']) {
                return true;
            }
            foreach ($c['permissions'] as $permission) {
                if ($user->hasPermissionInCompany($permission, $company)) {
                    return true;
                }
            }

            return false;
        });
    }

    /** Capítulo visible para la pantalla actual (botón "?"), o null. */
    public static function chapterForRoute(?string $routeName, array $visible): ?array
    {
        if (! $routeName) {
            return null;
        }
        foreach ($visible as $chapter) {
            foreach ($chapter['routes'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $chapter;
                }
            }
        }

        return null;
    }

    /**
     * HTML del capítulo + índice de sus secciones (##).
     *
     * @return array{html: string, toc: array<int, array{id: string, text: string}>}
     */
    public static function render(array $chapter, ?Company $company): array
    {
        $markdown = strtr($chapter['body'], [
            '{moneda}'  => currency_symbol($company),
            '{empresa}' => $company?->name ?? config('brand.name'),
        ]);

        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        $toc = [];
        $html = preg_replace_callback('#<h2>(.*?)</h2>#s', function ($m) use (&$toc, $chapter) {
            $text = trim(strip_tags($m[1]));
            $id   = $chapter['slug'] . '-' . Str::slug($text);
            $toc[] = ['id' => $id, 'text' => $text];

            return '<h2 id="' . $id . '">' . $m[1] . '</h2>';
        }, $html);

        return ['html' => $html, 'toc' => $toc];
    }

    private static function parse(string $file): array
    {
        $raw  = str_replace("\r\n", "\n", file_get_contents($file));
        $meta = [];
        $body = $raw;

        if (preg_match('/^---\n(.*?)\n---\n(.*)$/s', $raw, $m)) {
            foreach (explode("\n", $m[1]) as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $meta[trim($key)] = trim($value);
                }
            }
            $body = $m[2];
        }

        $list = fn (?string $v) => array_values(array_filter(array_map('trim', explode(',', (string) $v))));

        return [
            // "02-ventas-pos.md" → "ventas-pos"
            'slug'        => preg_replace('/^\d+-/', '', pathinfo($file, PATHINFO_FILENAME)),
            'title'       => $meta['title'] ?? pathinfo($file, PATHINFO_FILENAME),
            'icon'        => $meta['icon'] ?? 'bi-book',
            'summary'     => $meta['summary'] ?? '',
            'module'      => ($meta['module'] ?? '') ?: null,
            'permissions' => $list($meta['permissions'] ?? ''),
            'routes'      => $list($meta['routes'] ?? ''),
            'body'        => ltrim($body),
        ];
    }
}
