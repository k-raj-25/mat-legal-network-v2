@props(['eyebrow' => null, 'title', 'lead' => null, 'tone' => 'brand'])
<section class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10">
        <div class="dot-grid absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>
        <div class="absolute -top-24 -left-24 size-96 rounded-full {{ $tone === 'coral' ? 'bg-coral-300/40' : 'bg-brand-300/40' }} blur-3xl"></div>
        <div class="absolute -top-24 right-0 size-80 rounded-full {{ $tone === 'coral' ? 'bg-amber-200/50' : 'bg-coral-300/30' }} blur-3xl"></div>
    </div>
    <div class="container-page py-14 text-center sm:py-20">
        @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        <h1 class="mx-auto mt-5 max-w-4xl text-4xl leading-[1.05] sm:text-6xl">{{ $title }}</h1>
        @if ($lead)<p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-600">{{ $lead }}</p>@endif
        {{ $slot }}
    </div>
</section>
