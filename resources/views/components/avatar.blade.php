@props(['lawyer', 'size' => 'size-16', 'text' => 'text-lg'])
@php
    $gradients = ['from-brand-400 to-brand-700', 'from-coral-400 to-coral-600', 'from-violet-400 to-indigo-600', 'from-amber-300 to-orange-500', 'from-sky-400 to-blue-600', 'from-pink-400 to-rose-600'];
    $g = $gradients[crc32((string) $lawyer->public_code) % count($gradients)];
@endphp
@if ($lawyer->photoUrl())
    <img src="{{ $lawyer->photoUrl() }}" alt="Photo of {{ $lawyer->full_name }}" loading="lazy" class="{{ $size }} shrink-0 rounded-2xl object-cover ring-4 ring-white shadow-pop" draggable="false">
@else
    <span class="{{ $size }} {{ $text }} inline-flex shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $g }} font-display font-extrabold text-white ring-4 ring-white shadow-pop" aria-hidden="true">{{ $lawyer->initials() }}</span>
@endif
