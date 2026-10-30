@php
    // dompdf rinde mejor con la imagen embebida en base64 que con una URL/ruta.
    $logoData = ($companyLogo && is_file($companyLogo))
        ? 'data:' . (mime_content_type($companyLogo) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($companyLogo))
        : null;
    $docTitle = $single ? reset($chapters)['title'] : 'Manual de usuario';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }} — {{ $companyName }}</title>
    <style>
        @page { margin: 56px 48px 56px 48px; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; color: #1a1a1a; font-size: 11px; line-height: 1.55; margin: 0; }

        /* Pie fijo con número de página (dompdf: counter(page)) */
        .footer { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 9px; color: #888;
                  border-top: 1px solid #ddd; padding-top: 4px; }
        .footer .page:after { content: counter(page); }

        .cover { text-align: center; padding-top: 170px; }
        .cover img { height: 90px; width: auto; margin-bottom: 24px; }
        .cover h1 { font-size: 28px; margin: 0 0 8px; }
        .cover .company { font-size: 15px; color: #444; }
        .cover .date { font-size: 10px; color: #888; margin-top: 40px; }

        .toc h2 { font-size: 18px; border-bottom: 3px solid #e63946; padding-bottom: 6px; }
        .toc table { width: 100%; border-collapse: collapse; }
        .toc td { padding: 6px 4px; border-bottom: 1px solid #eee; vertical-align: top; }
        .toc .num { width: 26px; color: #e63946; font-weight: bold; }
        .toc .summary { color: #666; font-size: 9.5px; }

        .page-break { page-break-before: always; }

        .chapter-title { font-size: 20px; margin: 0 0 14px; padding-bottom: 6px; border-bottom: 3px solid #e63946; }
        .content h2 { font-size: 14px; margin: 18px 0 6px; color: #22242e; page-break-after: avoid; }
        .content h3 { font-size: 12px; margin: 12px 0 4px; page-break-after: avoid; }
        .content p { margin: 0 0 7px; }
        .content ol, .content ul { margin: 0 0 8px; padding-left: 20px; }
        .content li { margin-bottom: 3px; }
        .content blockquote { margin: 8px 0; padding: 6px 10px; background: #f6f7f9; border-left: 3px solid #e63946; }
        .content blockquote p { margin: 0; }
        .content code { font-family: "DejaVu Sans Mono", monospace; font-size: 10px; background: #f2f2f2; padding: 0 3px; }
        .content table { width: 100%; border-collapse: collapse; margin: 6px 0 10px; }
        .content th, .content td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; vertical-align: top; }
        .content th { background: #22242e; color: #fff; font-size: 10px; }
        .content img { max-width: 100%; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $companyName }} · {{ $docTitle }}
        <span style="float:right;">Página <span class="page"></span></span>
    </div>

    {{-- Portada --}}
    <div class="cover">
        @if($logoData)
            <img src="{{ $logoData }}" alt="logo">
        @endif
        <h1>{{ $docTitle }}</h1>
        <div class="company">{{ $companyName }}</div>
        <div class="date">Generado el {{ $generatedAt }}</div>
    </div>

    {{-- Índice (solo en el manual completo) --}}
    @unless($single)
        <div class="page-break toc">
            <h2>Contenido</h2>
            <table>
                @foreach(array_values($chapters) as $i => $chapter)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $chapter['title'] }}</strong>
                            @if($chapter['summary'])
                                <div class="summary">{{ $chapter['summary'] }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endunless

    @foreach(array_values($chapters) as $i => $chapter)
        <div class="page-break">
            <h1 class="chapter-title">{{ $single ? '' : ($i + 1) . '. ' }}{{ $chapter['title'] }}</h1>
            <div class="content">{!! $chapter['content']['html'] !!}</div>
        </div>
    @endforeach
</body>
</html>
