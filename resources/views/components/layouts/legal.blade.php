@props(['title', 'page', 'updated'])
{{-- The disclaimer popup links to these pages, so it must not cover them. --}}
<x-layouts.app :title="$title" :description="$title.' of MAT LEGAL NETWORK.'" :disclaimer="! in_array($page, \App\Http\Controllers\PageController::DISCLAIMER_FREE, true)">
    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="dot-grid absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>
            <div class="absolute -top-24 right-0 size-80 rounded-full bg-violet-300/30 blur-3xl"></div>
        </div>
        <div class="container-page py-12">
            <p class="eyebrow"><x-icon name="scale" class="size-4 text-brand-600"/> Legal</p>
            <h1 class="mt-4 text-4xl sm:text-5xl">{{ $title }}</h1>
            <p class="mt-3 text-sm font-semibold text-slate-500">Last updated: {{ $updated }}</p>
        </div>
    </div>
    <div class="container-page grid grid-cols-1 gap-10 py-10 lg:grid-cols-[240px_1fr]">
        <nav aria-label="Legal documents" class="min-w-0 lg:sticky lg:top-24 lg:self-start">
            <ul class="flex gap-2 overflow-x-auto pb-2 text-sm lg:flex-col lg:gap-1 lg:overflow-visible">
                @foreach (\App\Http\Controllers\PageController::LEGAL as $slug => $label)
                    <li class="shrink-0"><a href="{{ route('legal', $slug) }}" class="block rounded-full px-4 py-2 font-semibold transition {{ $page === $slug ? 'bg-ink text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-ink' }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>
        <article class="prose-legal min-w-0 max-w-3xl rounded-[28px] border border-slate-200/80 bg-white p-6 shadow-pop sm:p-10" data-testid="legal-content">
            {{ $slot }}
            <hr class="my-10 border-slate-200">
            <p class="text-sm text-slate-500">Questions about this document? Write to <a href="mailto:{{ $site['company_email'] }}">{{ $site['company_email'] }}</a> or contact our <a href="{{ route('legal', 'grievance') }}">Grievance Officer</a>.</p>
        </article>
    </div>
</x-layouts.app>
