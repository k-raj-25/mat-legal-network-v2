@props(['light' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg class="size-9 shrink-0" viewBox="0 0 40 40" aria-hidden="true">
        <rect width="40" height="40" rx="10" fill="{{ $light ? '#ffffff' : '#142540' }}"/>
        <path d="M20 8v22M13 30h14M11 13h18" stroke="{{ $light ? '#142540' : '#dbb96b' }}" stroke-width="2" stroke-linecap="round"/>
        <path d="M11 13l-4 8a4 4 0 0 0 8 0l-4-8ZM29 13l-4 8a4 4 0 0 0 8 0l-4-8Z" fill="none" stroke="{{ $light ? '#142540' : '#dbb96b' }}" stroke-width="1.8" stroke-linejoin="round"/>
    </svg>
    <span class="leading-none">
        <span class="block font-display text-[17px] font-bold tracking-wide {{ $light ? 'text-white' : 'text-brand-900' }}">MAT LEGAL</span>
        <span class="block text-[10px] font-semibold tracking-[0.32em] {{ $light ? 'text-gold-200' : 'text-gold-600' }}">NETWORK</span>
    </span>
</span>
