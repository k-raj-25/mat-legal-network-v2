@props(['name', 'alt' => '', 'eager' => false])
{{-- Original brand illustrations (built by scripts/illustrations/build.py). Sizes are fixed here so images never shift the layout. --}}
@php
    $sizes = [
        'hero-about' => [640, 560], 'hero-for-lawyers' => [640, 560], 'hero-contact' => [640, 560],
        'step-search' => [480, 300], 'step-call' => [480, 300], 'step-connect' => [480, 300],
        'cta-help' => [300, 300], 'cta-advocate' => [300, 300], 'scales-light' => [300, 300],
        'city-skyline' => [1200, 240], 'find-lawyer' => [560, 280], 'empty-search' => [340, 260],
        'auth-login' => [340, 420], 'auth-join' => [340, 420], 'error-scales' => [420, 300],
    ];
    [$w, $h] = $sizes[$name] ?? [800, 450];
@endphp
<img src="{{ asset('images/illustrations/'.$name.'.svg') }}" alt="{{ $alt }}" width="{{ $w }}" height="{{ $h }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if ($alt === '') aria-hidden="true" @endif {{ $attributes }}>
