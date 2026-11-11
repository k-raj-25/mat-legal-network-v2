@props(['lawyer', 'size' => 'size-16', 'text' => 'text-lg'])
@if ($lawyer->photoUrl())
    <img src="{{ $lawyer->photoUrl() }}" alt="Photo of {{ $lawyer->full_name }}" loading="lazy" class="{{ $size }} shrink-0 rounded-xl object-cover ring-1 ring-slate-200" draggable="false">
@else
    <span class="{{ $size }} {{ $text }} inline-flex shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-700 to-brand-900 font-display font-semibold text-gold-200 ring-1 ring-brand-900/10" aria-hidden="true">{{ $lawyer->initials() }}</span>
@endif
