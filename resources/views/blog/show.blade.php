@php
    $metaTitle = $post->meta_title ?: $post->title;
    $metaDescription = $post->meta_description ?: $post->summary(160);
    $url = route('blog.show', $post->slug);
    $image = $post->imageUrl() ? url($post->imageUrl()) : null;
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => Str::limit($post->title, 110, ''),
        'description' => $metaDescription,
        'mainEntityOfPage' => $url,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Person', 'name' => $post->authorName()],
        'publisher' => ['@type' => 'Organization', 'name' => $site['legal_entity_name'], 'logo' => ['@type' => 'ImageObject', 'url' => asset('favicon.svg')]],
        'image' => $image,
        'articleSection' => $post->category?->name,
        'wordCount' => str_word_count(strip_tags((string) $post->body)),
    ];
@endphp
<x-layouts.app :title="$metaTitle" :description="$metaDescription" :canonical="$url" :noindex="$preview" og-type="article" :og-image="$image" :disclaimer="! $preview">
    <x-slot:head>
        @unless ($preview)
            <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
            <meta property="article:modified_time" content="{{ $post->updated_at?->toIso8601String() }}">
            @if ($post->category)<meta property="article:section" content="{{ $post->category->name }}">@endif
            <script type="application/ld+json">{!! json_encode(array_filter($jsonLd), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endunless
    </x-slot:head>

    @if ($preview)
        <div class="sticky top-[72px] z-30 bg-amber-100 text-amber-900 ring-1 ring-amber-200" role="status" data-testid="preview-banner">
            <div class="container-page flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm font-semibold">
                <span><x-icon name="eye" class="mr-1 inline size-4"/> Preview{{ $post->isPublished() ? ' of the live post' : ': this draft is not on the blog yet' }}.</span>
                <a href="{{ route('admin.posts.edit', $post) }}" class="font-bold underline underline-offset-2">Back to editing</a>
            </div>
        </div>
    @endif

    <article data-testid="blog-post">
        <header class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="dot-grid absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>
                <div class="absolute -top-24 -left-24 size-80 rounded-full bg-brand-300/35 blur-3xl"></div>
                <div class="absolute -top-24 right-0 size-80 rounded-full bg-coral-300/30 blur-3xl"></div>
            </div>
            <div class="container-page max-w-4xl pt-6 pb-8 sm:pt-10">
                <nav class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-ink">Home</a> <x-icon name="chevron-right" class="size-3"/>
                    <a href="{{ route('blog.index') }}" class="hover:text-ink">Blog</a>
                    @if ($post->category)
                        <x-icon name="chevron-right" class="size-3"/> <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:text-ink">{{ $post->category->name }}</a>
                    @endif
                </nav>
                @if ($post->category)
                    <a href="{{ route('blog.category', $post->category->slug) }}" class="chip mt-6 hover:bg-brand-100">{{ $post->category->name }}</a>
                @endif
                <h1 class="mt-4 text-4xl leading-[1.08] break-words sm:text-5xl" data-testid="post-title">{{ $post->title }}</h1>
                @if ($post->excerpt)<p class="mt-4 text-lg leading-8 text-slate-600 sm:text-xl">{{ $post->excerpt }}</p>@endif
                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold text-slate-600">
                    <span class="inline-flex items-center gap-2"><span class="tile size-8 bg-brand-100 text-brand-700"><x-icon name="user" class="size-4"/></span>{{ $post->authorName() }}</span>
                    @if ($post->published_at)
                        <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="size-4 text-slate-500"/><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time></span>
                    @endif
                    <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="size-4 text-slate-500"/>{{ $post->reading_minutes }} min read</span>
                </div>
            </div>
        </header>

        <div class="container-page max-w-4xl">
            @if ($post->featured_image_path)
                <figure class="overflow-hidden rounded-[28px] bg-slate-100 shadow-pop">
                    <img src="{{ $post->imageUrl() }}" alt="{{ $post->featured_image_alt }}" class="aspect-[16/9] w-full object-cover" fetchpriority="high" decoding="async">
                </figure>
            @endif

            <div class="prose-blog mx-auto mt-10 max-w-3xl" data-testid="post-body">
                {!! $post->body !!}
            </div>

            <aside class="mx-auto mt-12 max-w-3xl rounded-3xl bg-coral-50 p-5 text-sm leading-6 text-slate-700 ring-1 ring-coral-200 sm:p-6" data-testid="post-disclaimer" aria-label="Disclaimer">
                <p class="flex items-center gap-2 font-display font-extrabold text-ink"><x-icon name="scale" class="size-5 text-coral-600"/> Please note</p>
                <p class="mt-2">This article is general information about the law. It is not legal advice on your matter, and reading it does not create a lawyer–client relationship. {{ $site['legal_entity_name'] }} is a law firm and gives legal advice when you engage it for your matter. Laws change, so please check the current position before acting. Read our <a href="{{ route('legal', 'disclaimer') }}" class="font-bold text-brand-700 underline underline-offset-2">Disclaimer</a>.</p>
            </aside>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="container-page mt-16" aria-labelledby="related-heading">
            <h2 id="related-heading" class="text-3xl">More {{ $post->category ? 'on '.strtolower($post->category->name) : 'from the blog' }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $p)<x-post-card :post="$p" heading="h3"/>@endforeach
            </div>
        </section>
    @endif

    @include('blog.partials.cta')
</x-layouts.app>
