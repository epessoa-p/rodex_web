{{-- Tabla compacta de actividad del cliente.
     Props: $rows (Collection), $head (array de encabezados), $render (closure → array de celdas),
            $empty (texto vacío), $icon (icono empty state).
     Cada celda: ['text'=>, 'link'=>, 'badge'=>, 'color'=>, 'mono'=>, 'muted'=>, 'bold'=>, 'end'=>] --}}
@if($rows->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi {{ $icon }} fs-2 opacity-25 d-block mb-2"></i>
        <span class="small">{{ $empty }}</span>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
            <thead>
                <tr class="table-light border-bottom">
                    @foreach($head as $h)
                    <th class="{{ $loop->first ? 'ps-4' : '' }} {{ $loop->last ? 'pe-4 text-end' : '' }} py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php
                    $cells = $render($row);
                    $rowLink = null;
                    foreach ($cells as $c) { if (!empty($c['link'])) { $rowLink = $c['link']; break; } }
                @endphp
                <tr class="border-bottom border-light {{ $rowLink ? 'activity-row' : '' }}" @if($rowLink) data-href="{{ $rowLink }}" @endif>
                    @foreach($cells as $i => $cell)
                    <td class="{{ $i === 0 ? 'ps-4' : '' }} {{ $loop->last ? 'pe-4' : '' }} {{ ($cell['end'] ?? false) || $loop->last ? 'text-end' : '' }} py-2 {{ ($cell['muted'] ?? false) ? 'text-muted' : '' }} {{ ($cell['bold'] ?? false) ? 'fw-semibold' : '' }} {{ ($cell['mono'] ?? false) ? 'font-monospace' : '' }}">
                        @if(isset($cell['badge']))
                            <span class="badge bg-{{ $cell['color'] ?? 'secondary' }}-subtle text-{{ $cell['color'] ?? 'secondary' }} border border-{{ $cell['color'] ?? 'secondary' }}-subtle" style="font-size:.68rem;">{{ $cell['badge'] }}</span>
                        @elseif(isset($cell['link']))
                            <a href="{{ $cell['link'] }}" class="text-decoration-none fw-semibold text-dark">{{ $cell['text'] }}</a>
                        @else
                            {{ $cell['text'] }}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
