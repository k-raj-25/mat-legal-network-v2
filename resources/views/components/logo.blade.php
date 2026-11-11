@props(['light' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg class="size-10 shrink-0" viewBox="0 0 40 40" aria-hidden="true">
        <defs>
            <linearGradient id="mlnG" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                <stop stop-color="#14b8a0"/><stop offset="1" stop-color="#0a7f78"/>
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="12" fill="url(#mlnG)"/>
        <path d="M20 9v20M13.5 29h13M11 14h18" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/>
        <path d="M11 14l-3.6 7.2a3.6 3.6 0 0 0 7.2 0L11 14ZM29 14l-3.6 7.2a3.6 3.6 0 0 0 7.2 0L29 14Z" fill="none" stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
        <circle cx="33" cy="7" r="4.5" fill="#ff5a45" stroke="{{ $light ? '#0b1324' : '#fff' }}" stroke-width="2"/>
    </svg>
    <span class="leading-none">
        <span class="block font-display text-[17px] font-extrabold tracking-[-0.03em] {{ $light ? 'text-white' : 'text-ink' }}">MAT Legal</span>
        <span class="mt-0.5 block text-[10px] font-bold tracking-[0.28em] {{ $light ? 'text-brand-300' : 'text-brand-600' }}">NETWORK</span>
    </span>
</span>
