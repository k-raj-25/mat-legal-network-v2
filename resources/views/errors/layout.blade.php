<x-layouts.app :title="$title" noindex>
    <div class="container-page py-24 text-center">
        <p class="font-display text-6xl font-bold text-gold-500">{{ $code }}</p>
        <h1 class="mt-4 text-3xl font-bold text-brand-950">{{ $title }}</h1>
        <p class="mx-auto mt-3 max-w-md text-slate-600">{{ $message }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-primary">Go to home page</a>
            <a href="{{ route('lawyers.index') }}" class="btn-outline">Find a lawyer</a>
        </div>
    </div>
</x-layouts.app>
