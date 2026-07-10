{{-- Checklist de componentes + fotos para inspección de salida/entrada --}}
<hr class="my-4">
<h6 class="fw-semibold small text-uppercase text-muted mb-3" style="letter-spacing:.04em;"><i class="bi bi-card-checklist me-1"></i>Checklist de inspección</h6>
<div class="row g-2">
    @foreach(\App\Models\Rentals\RentalInspection::CHECKLIST_ITEMS as $key => $label)
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2 border rounded-2 px-2 py-1">
            <span class="small flex-grow-1">{{ $label }}</span>
            <select name="checklist[{{ $key }}][condition]" class="form-select form-select-sm" style="width:auto;">
                <option value="">—</option>
                @foreach(\App\Models\Rentals\RentalInspection::CONDITIONS as $cv => $cl)
                <option value="{{ $cv }}">{{ $cl }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3">
    <label class="form-label fw-semibold small"><i class="bi bi-camera me-1"></i>Fotos (opcional)</label>
    <x-media-upload name="photos[]" :multiple="true" :bare="true"
        accent="#e10600" :max-mb="5" :max-files="12"
        drop-text="Arrastra fotos aquí"
        hint="JPG, PNG, WebP · máx. 5MB" />
</div>
