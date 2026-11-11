<x-layouts.app :title="$title" noindex>
    <div class="container-page py-24 text-center">
        <p class="font-display text-8xl font-extrabold tracking-tighter text-transparent sm:text-9xl" style="-webkit-text-stroke: 3px var(--color-coral-500)">{{ $code }}</p>
        <h1 class="mt-4 text-4xl">{{ $title }}</h1>
        <p class="mx-auto mt-3 max-w-md text-lg text-slate-600">{{ $message }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-ink px-6 py-3">Go to home page</a>
            <a href="{{ route('lawyers.index') }}" class="btn-outline px-6 py-3">Find a lawyer</a>
        </div>
    </div>
</x-layouts.app>
