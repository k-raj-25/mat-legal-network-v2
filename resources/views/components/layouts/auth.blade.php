@props(['title', 'subtitle' => null, 'wide' => false])
<x-layouts.app :title="$title" noindex :disclaimer="false">
    <div class="bg-slate-50 py-10 sm:py-16">
        <div class="container-page">
            <div class="mx-auto {{ $wide ? 'max-w-3xl' : 'max-w-md' }}">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-brand-950">{{ $title }}</h1>
                    @if ($subtitle)<p class="mt-2 text-slate-600">{{ $subtitle }}</p>@endif
                </div>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
