@php
    $page = $posts->currentPage();
    $heading = $category ? $category->name : 'Legal insights';
    $title = ($category ? $category->name.' – Blog' : 'Blog – Legal insights & guides').($page > 1 ? ' (page '.$page.')' : '');
    $description = $category
        ? 'Articles on '.strtolower($category->name).' from MAT LEGAL NETWORK: plain-language guides on Indian law, your rights and the legal process.'
        : 'Plain-language guides on Indian law, your rights and the legal process, from the advocates at MAT LEGAL NETWORK.';
    $canonical = $category ? route('blog.category', $category->slug) : route('blog.index');
    if ($page > 1) $canonical .= '?page='.$page;
    $lead = $page === 1 ? $posts->first() : null;
    $rest = $lead ? $posts->slice(1) : $posts;
@endphp
<x-layouts.app :title="$title" :description="$description" :canonical="$canonical">
    <x-slot:head>
        @if ($posts->previousPageUrl())<link rel="prev" href="{{ $posts->previousPageUrl() }}">@endif
        @if ($posts->nextPageUrl())<link rel="next" href="{{ $posts->nextPageUrl() }}">@endif
    </x-slot:head>

    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="dot-grid absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>
            <div class="absolute -top-24 -left-24 size-80 rounded-full bg-brand-300/35 blur-3xl"></div>
            <div class="absolute -top-24 right-0 size-80 rounded-full bg-coral-300/30 blur-3xl"></div>
        </div>
        <div class="container-page pt-6 pb-8 sm:pt-10">
            <nav class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-ink">Home</a> <x-icon name="chevron-right" class="size-3"/>
                @if ($category)
                    <a href="{{ route('blog.index') }}" class="hover:text-ink">Blog</a> <x-icon name="chevron-right" class="size-3"/> <span class="text-ink">{{ $category->name }}</span>
                @else
                    <span class="text-ink">Blog</span>
                @endif
            </nav>
            <p class="eyebrow mt-5"><x-icon name="newspaper" class="size-4 text-coral-600"/> MAT LEGAL NETWORK blog</p>
            <h1 class="mt-4 text-4xl sm:text-5xl" data-testid="blog-heading">{{ $heading }}</h1>
            <p class="mt-3 max-w-2xl text-lg leading-8 text-slate-600">{{ $category ? 'Guides and updates on '.strtolower($category->name).'.' : 'Plain-language guides on Indian law, your rights and the legal process, written by our team.' }}</p>

            @if ($categories->isNotEmpty())
                <nav class="mt-6 -mx-4 overflow-x-auto px-4 pb-1" aria-label="Blog categories">
                    <ul class="flex w-max gap-2">
                        <li><a href="{{ route('blog.index') }}" @if (! $category) aria-current="page" @endif class="block rounded-full px-4 py-2 text-sm font-bold transition {{ ! $category ? 'bg-ink text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:text-ink hover:ring-ink' }}">All posts</a></li>
                        @foreach ($categories as $c)
                            <li><a href="{{ route('blog.category', $c->slug) }}" @if ($category?->id === $c->id) aria-current="page" @endif class="block rounded-full px-4 py-2 text-sm font-bold whitespace-nowrap transition {{ $category?->id === $c->id ? 'bg-ink text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:text-ink hover:ring-ink' }}">{{ $c->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </div>

    <div class="container-page py-6">
        @if ($posts->isEmpty())
            <div class="card px-6 py-16 text-center">
                <span class="tile size-14 bg-brand-100 text-brand-700"><x-icon name="newspaper" class="size-7"/></span>
                <h2 class="mt-4 text-2xl">No articles here yet</h2>
                <p class="mt-2 text-slate-600">New guides are on the way. In the meantime, talk to our team about your matter.</p>
                <a href="{{ route('contact') }}" class="btn-primary mt-6">Contact us</a>
            </div>
        @else
            @if ($lead)
                <x-post-card :post="$lead" lead class="mb-6"/>
            @endif
            @if ($rest->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-testid="post-grid">
                    @foreach ($rest as $p)<x-post-card :post="$p"/>@endforeach
                </div>
            @endif
            <div class="mt-10">{{ $posts->onEachSide(1)->links() }}</div>
        @endif
    </div>

    @include('blog.partials.cta')
</x-layouts.app>
