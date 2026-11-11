{{-- Clients always contact the COMPANY, never the lawyer directly. --}}
@props(['lawyer' => null, 'stacked' => false, 'onDark' => false])
@php
    $msg = $lawyer
        ? "Hello MAT LEGAL NETWORK, I would like to consult {$lawyer->full_name} (Profile ID: {$lawyer->public_code})."
        : 'Hello MAT LEGAL NETWORK, I need help finding a lawyer.';
    $wa = 'https://wa.me/'.preg_replace('/\D/', '', $site['company_whatsapp']).'?text='.rawurlencode($msg);
    $tel = 'tel:'.preg_replace('/[^\d+]/', '', $site['company_phone']);
@endphp
<div {{ $attributes->merge(['class' => $stacked ? 'grid gap-2.5' : 'flex flex-wrap gap-2.5']) }}>
    <a href="{{ $tel }}" class="{{ $onDark ? 'btn bg-white text-brand-900 shadow-sm hover:bg-gold-50' : 'btn-primary' }} {{ $stacked ? 'w-full py-3' : '' }}" data-testid="call-company">
        <x-icon name="phone" class="size-4"/> Call {{ $site['company_phone'] }}
    </a>
    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-whatsapp {{ $stacked ? 'w-full py-3' : '' }}" data-testid="whatsapp-company">
        <x-icon name="whatsapp" class="size-4"/> WhatsApp us
    </a>
</div>
