@props([
    'name',                       // name del input (ej. "photo" o "photos[]")
    'multiple' => false,
    'label' => 'Imagen',
    'subtitle' => null,
    'icon' => 'bi-image',
    'accept' => 'image/*',
    'maxMb' => 2,
    'maxFiles' => null,
    'hint' => null,
    'dropText' => 'Arrastra aquí',
    'current' => null,            // (single) URL de imagen existente para previsualizar
    'camera' => true,
    'accent' => '#2563eb',
    'bare' => false,             // true = sin tarjeta/encabezado (para incrustar en otra card)
    'uploadLabel' => 'Subir',
])
@php
    $uid  = 'mu_' . \Illuminate\Support\Str::random(6);
    $hintText = $hint ?: ('JPG, PNG, WebP · máx. ' . $maxMb . 'MB');
@endphp
<div class="media-uploader {{ $bare ? '' : 'card-box' }}" data-uploader id="{{ $uid }}"
     data-multiple="{{ $multiple ? 1 : 0 }}"
     data-max-mb="{{ $maxMb }}"
     @if($maxFiles) data-max-files="{{ $maxFiles }}" @endif
     @if($current) data-current="{{ $current }}" @endif
     style="--mu-accent:{{ $accent }};">

    @unless($bare)
    <div class="mu-head">
        <span class="mu-head-icon"><i class="bi {{ $icon }}"></i></span>
        <div>
            <div class="mu-head-title">{{ $label }}</div>
            @if($subtitle)<div class="mu-head-sub">{{ $subtitle }}</div>@endif
        </div>
    </div>
    @endunless

    <div class="mu-tabs">
        <button type="button" class="mu-tab active" data-pane="upload">
            <i class="bi bi-upload"></i> {{ $uploadLabel }}
        </button>
        @if($camera)
        <button type="button" class="mu-tab" data-pane="camera">
            <i class="bi bi-camera-video"></i> Cámara
        </button>
        @endif
    </div>

    {{-- Subir / arrastrar --}}
    <div class="mu-pane mu-pane-upload">
        <label class="mu-drop">
            <span class="mu-drop-icon"><i class="bi {{ $icon }}"></i></span>
            <span class="mu-drop-text">{{ $dropText }}</span>
            <span class="mu-drop-hint">{{ $hintText }}</span>
            <input type="file" name="{{ $name }}" class="mu-input d-none"
                   accept="{{ $accept }}" {{ $multiple ? 'multiple' : '' }}>
        </label>
    </div>

    {{-- Cámara --}}
    @if($camera)
    <div class="mu-pane mu-pane-camera d-none">
        <div class="mu-cam-frame">
            <video class="mu-video" autoplay playsinline muted></video>
            <div class="mu-cam-off"><i class="bi bi-camera-video-off"></i><span>Cámara apagada</span></div>
        </div>
        <div class="mu-cam-actions">
            <button type="button" class="mu-btn mu-btn-light" data-cam="start"><i class="bi bi-camera-video me-1"></i>Iniciar</button>
            <button type="button" class="mu-btn mu-btn-accent d-none" data-cam="snap"><i class="bi bi-camera me-1"></i>Capturar</button>
            <button type="button" class="mu-btn mu-btn-light d-none" data-cam="stop">Detener</button>
        </div>
        <canvas class="mu-canvas d-none"></canvas>
    </div>
    @endif

    <div class="mu-previews"></div>

    @include('partials.uploaders._assets')
</div>
