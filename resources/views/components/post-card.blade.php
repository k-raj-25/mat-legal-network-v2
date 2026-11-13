@props(['post', 'lead' => false, 'heading' => 'h2'])
{{-- Blog post card. The title link covers the whole card; the category is a separate link above it. --}}
<article {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-pop transition duration-200 hover:-translate-y-1 hover:shadow-pop-lg focus-within:ring-2 focus-within:ring-brand-500 '.($lead ? 'lg:grid lg:grid-cols-[1.25fr_1fr]' : '')]) }} data-testid="post-card">
    <div class="relative overflow-hidden bg-gradient-to-br from-brand-500 via-brand-400 to-coral-400 {{ $lead ? 'aspect-[16/9] lg:aspect-auto lg:min-h-80' : 'aspect-[16/9]' }}">
        @if ($post->featured_image_path)
            <img src="{{ $post->imageUrl() }}" alt="{{ $post->featured_image_alt }}" loading="{{ $lead ? 'eager' : 'lazy' }}" decoding="async" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <img src="{{ $post->coverArtUrl() }}" alt="" aria-hidden="true" width="800" height="450" loading="{{ $lead ? 'eager' : 'lazy' }}" decoding="async" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.03]" data-testid="post-cover-art">
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5 sm:p-6 {{ $lead ? 'lg:justify-center lg:p-10' : '' }}">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold text-slate-500">
            @if ($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}" class="relative z-10 chip hover:bg-brand-100">{{ $post->category->name }}</a>
            @endif
            <span class="inline-flex items-center gap-1"><x-icon name="clock" class="size-3.5"/> {{ $post->reading_minutes }} min read</span>
        </div>
        <{{ $heading }} class="mt-3 {{ $lead ? 'text-2xl sm:text-3xl lg:text-4xl' : 'text-xl' }} leading-tight">
            <a href="{{ route('blog.show', $post->slug) }}" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-none">{{ $post->title }}</a>
        </{{ $heading }}>
        <p class="mt-3 flex-1 leading-7 text-slate-600 {{ $lead ? 'sm:text-lg' : 'line-clamp-3 text-[15px]' }}">{{ $post->summary($lead ? 240 : 160) }}</p>
        <p class="mt-5 text-sm font-semibold text-slate-600">
            {{ $post->authorName() }} · <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('d M Y') }}</time>
        </p>
    </div>
</article>
