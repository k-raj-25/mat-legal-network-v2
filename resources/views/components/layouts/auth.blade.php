@props(['title', 'subtitle' => null, 'wide' => false])
<x-layouts.app :title="$title" noindex :disclaimer="false">
    <div class="relative overflow-hidden py-10 sm:py-16">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="dot-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,black_20%,transparent_70%)]"></div>
            <div class="absolute -top-32 -left-32 size-[30rem] rounded-full bg-brand-300/40 blur-3xl"></div>
            <div class="absolute top-20 -right-32 size-[26rem] rounded-full bg-coral-300/35 blur-3xl"></div>
        </div>
        <div class="container-page">
            <div class="mx-auto {{ $wide ? 'max-w-3xl' : 'max-w-md' }}">
                <div class="mb-8 text-center">
                    <h1 class="text-4xl sm:text-5xl">{{ $title }}</h1>
                    @if ($subtitle)<p class="mx-auto mt-3 max-w-lg text-lg text-slate-600">{{ $subtitle }}</p>@endif
                </div>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
