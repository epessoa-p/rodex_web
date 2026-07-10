@php
    $isEdit  = isset($documentTemplate);
    $action  = $isEdit ? route('document-templates.update', $documentTemplate) : route('document-templates.store');
    $method  = $isEdit ? 'PUT' : 'POST';
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST" id="template-form">
    @csrf
    @method($method)

    <div class="row g-4">

        {{-- ── Left column: fields + rich editor ─────────────────── --}}
        <div class="col-lg-8">

            {{-- Name, Type, Description, Active --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="name">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $documentTemplate->name ?? '') }}"
                                   required maxlength="255"
                                   placeholder="Ej: Contrato estándar de préstamo">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="type">
                                Tipo <span class="text-danger">*</span>
                            </label>
                            <select id="type" name="type"
                                    class="form-select @error('type') is-invalid @enderror" required>
                                @foreach(\App\Models\DocumentTemplate::TYPES as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('type', $documentTemplate->type ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold" for="description">
                            Descripción
                            <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <textarea id="description" name="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="2" maxlength="1000"
                                  placeholder="Breve descripción del uso de esta plantilla...">{{ old('description', $documentTemplate->description ?? '') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mt-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $documentTemplate->active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Plantilla activa</label>
                    </div>
                </div>
            </div>

            {{-- Rich text editor --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> Contenido de la plantilla</h6>
                    <small class="text-muted d-none d-md-block">
                        Usa las variables del panel derecho para personalizar el documento
                    </small>
                </div>
                <div class="card-body p-0">
                    <textarea id="content" name="content" class="form-control border-0" rows="22"
                              placeholder="Escribe o pega el contenido de la plantilla aquí...">{{ old('content', $documentTemplate->content ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Right column: placeholder variables ─────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="position: sticky; top: 1rem; max-height: 88vh; overflow-y: auto;">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-braces"></i> Variables disponibles</h6>
                    <small class="text-muted">Haz clic para insertar en el editor</small>
                </div>
                <div class="card-body p-2">
                    @php
                        $placeholders = \App\Models\DocumentTemplate::PLACEHOLDERS;
                    @endphp
                    @foreach($placeholders as $key => $label)
                        @php($token = sprintf('{{%s}}', $key))
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary w-100 text-start mb-1 insert-var-btn"
                                data-var="{{ $token }}"
                                title="{{ $label }}">
                            <code class="text-primary" style="font-size: .73rem;">{{ $token }}</code>
                            <span class="text-muted ms-1" style="font-size: .72rem;">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('document-templates.index') }}" class="btn btn-light border">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i>
            {{ $isEdit ? 'Actualizar plantilla' : 'Crear plantilla' }}
        </button>
    </div>
</form>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
    (() => {
        const $editor = $('#content');
        const hasSummernote = typeof $editor.summernote === 'function';

        if (hasSummernote) {
            $editor.summernote({
                placeholder: 'Escribe o pega el contenido de la plantilla aquí...',
                tabsize: 2,
                height: 520,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'hr']],
                    ['view', ['codeview', 'help', 'fullscreen']],
                    ['history', ['undo', 'redo']]
                ]
            });
        }

        document.querySelectorAll('.insert-var-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const varText = btn.dataset.var;
                if (hasSummernote) {
                    $editor.summernote('focus');
                    $editor.summernote('pasteHTML', varText);
                } else {
                    // Fallback: copy to clipboard
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(varText).then(() => {
                            btn.classList.add('btn-success');
                            setTimeout(() => btn.classList.remove('btn-success'), 900);
                        });
                    }
                }
            });
        });
    })();
</script>
@endpush
