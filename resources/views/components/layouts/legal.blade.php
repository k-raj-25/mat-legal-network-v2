@props(['title', 'page', 'updated'])
<x-layouts.app :title="$title" :description="$title.' of MAT LEGAL NETWORK.'">
    <div class="border-b border-slate-200 bg-slate-50">
        <div class="container-page py-10">
            <p class="text-xs font-semibold tracking-widest text-gold-600 uppercase">Legal</p>
            <h1 class="mt-2 text-3xl font-bold text-brand-950 sm:text-4xl">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-500">Last updated: {{ $updated }}</p>
        </div>
    </div>
    <div class="container-page grid grid-cols-1 gap-10 py-10 lg:grid-cols-[240px_1fr]">
        <nav aria-label="Legal documents" class="min-w-0 lg:sticky lg:top-24 lg:self-start">
            <ul class="flex gap-2 overflow-x-auto pb-2 text-sm lg:flex-col lg:gap-1 lg:overflow-visible">
                @foreach (\App\Http\Controllers\PageController::LEGAL as $slug => $label)
                    <li class="shrink-0"><a href="{{ route('legal', $slug) }}" class="block rounded-lg px-3 py-2 {{ $page === $slug ? 'bg-brand-50 font-semibold text-brand-800' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>
        <article class="prose-legal min-w-0 max-w-3xl" data-testid="legal-content">
            {{ $slot }}
            <hr class="my-10 border-slate-200">
            <p class="text-sm text-slate-500">Questions about this document? Write to <a href="mailto:{{ $site['company_email'] }}">{{ $site['company_email'] }}</a> or contact our <a href="{{ route('legal', 'grievance') }}">Grievance Officer</a>.</p>
        </article>
    </div>
</x-layouts.app>
