@php
    $editing = $post->exists;
    $published = $post->isPublished();
    $catOptions = $categories->mapWithKeys(fn ($c) => [$c->id => $c->name])->all();
@endphp
<x-layouts.admin :title="$editing ? 'Edit post' : 'New post'">
    <x-slot:actions>
        <a href="{{ route('admin.posts.index') }}" class="btn-ghost"><x-icon name="chevron-left" class="size-4"/> <span class="max-sm:sr-only">All posts</span></a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('admin.posts.update', $post) : route('admin.posts.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_340px]" data-testid="post-form" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="min-w-0 space-y-6">
            <section class="card space-y-5 p-5 sm:p-7">
                <x-input name="title" label="Title" required :value="$post->title" maxlength="160" placeholder="e.g. What to do when a cheque bounces" class="[&_input]:text-lg [&_input]:font-bold"/>
                <div>
                    <label for="f_slug" class="form-label">Web address</label>
                    <div class="flex min-w-0 items-stretch overflow-hidden rounded-xl border-2 border-slate-200 bg-slate-50 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/15">
                        <span class="flex items-center pl-3.5 text-sm text-slate-500 select-none" aria-hidden="true">/blog/</span>
                        <input id="f_slug" name="slug" value="{{ old('slug', $post->slug) }}" maxlength="160" class="min-w-0 flex-1 bg-white px-2 py-2.5 text-sm text-ink focus:outline-none" aria-describedby="f_slug_help" aria-invalid="{{ $errors->has('slug') ? 'true' : 'false' }}" placeholder="made-from-the-title">
                    </div>
                    <p id="f_slug_help" class="form-help">Leave empty to make it from the title. Changing it on a live post breaks old links.</p>
                    @error('slug')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <x-input name="excerpt" type="textarea" rows="2" label="Summary" :value="$post->excerpt" maxlength="300" help="One or two sentences shown on the blog page and in search results. Up to 300 characters."/>
            </section>

            <section class="card p-5 sm:p-7">
                @include('admin.posts.partials.editor', ['value' => old('body', $post->body)])
            </section>
        </div>

        <aside class="min-w-0 space-y-6 xl:sticky xl:top-24 xl:self-start">
            <section class="card p-5" data-testid="publish-box">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg">Status</h2>
                    <x-status-badge :status="$post->status ?? \App\Enums\PostStatus::Draft"/>
                </div>
                <p class="mt-2 text-sm text-slate-600">
                    @if ($published)
                        Live on the blog since {{ $post->published_at->format('d M Y') }}.
                    @else
                        Drafts are only visible to you{{ auth()->user()->isAdmin() ? '' : ' and the admin' }}.
                    @endif
                </p>
                @if ($editing && auth()->user()->isAdmin())<p class="mt-1 text-xs text-slate-500">Written by {{ $post->authorName() }}</p>@endif
                <div class="mt-4 grid gap-2">
                    @if ($published)
                        <button name="action" value="save" class="btn-primary" data-testid="save-post">Update post</button>
                        <button name="action" value="unpublish" class="btn-outline" data-testid="unpublish-post">Unpublish (back to draft)</button>
                    @else
                        <button name="action" value="save" class="btn-outline" data-testid="save-draft">Save draft</button>
                        <button name="action" value="publish" class="btn-accent" data-testid="publish-post">Publish</button>
                    @endif
                </div>
                @if ($editing)
                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-slate-100 pt-4 text-sm font-bold">
                        @if ($published)
                            <a href="{{ route('blog.show', $post) }}" target="_blank" rel="noopener" class="text-brand-700 hover:underline">View on blog<span class="sr-only"> (opens in a new tab)</span></a>
                        @endif
                        <a href="{{ route('admin.posts.preview', $post) }}" target="_blank" rel="noopener" class="text-brand-700 hover:underline" data-testid="preview-link">Preview<span class="sr-only"> (opens in a new tab)</span></a>
                    </div>
                @endif
            </section>

            <section class="card space-y-4 p-5">
                <h2 class="text-lg">Category</h2>
                <x-select name="blog_category_id" label="Category" :options="$catOptions" :value="$post->blog_category_id" placeholder="No category" class="[&_label]:sr-only"/>
            </section>

            <section class="card p-5" x-data="{ preview: null, remove: false }">
                <h2 class="text-lg">Featured image</h2>
                <div class="mt-3 overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200" x-show="preview || {{ $post->featured_image_path ? 'true' : 'false' }}" x-cloak>
                    <img :src="preview || @js($post->imageUrl())" alt="" class="aspect-[16/9] w-full object-cover" :class="remove && !preview ? 'opacity-30' : ''">
                </div>
                <div class="mt-3">
                    <label for="f_featured_image" class="form-label">{{ $post->featured_image_path ? 'Replace image' : 'Upload image' }}</label>
                    <input id="f_featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="form-input file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-bold file:text-brand-700" aria-describedby="f_featured_image_help"
                           @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null">
                    <p id="f_featured_image_help" class="form-help">JPG, PNG or WebP, up to 4 MB. A wide image (1600 × 900) looks best.</p>
                    @error('featured_image')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                @if ($post->featured_image_path)
                    <label class="mt-3 flex items-center gap-2 text-sm font-semibold text-ink"><input type="checkbox" name="remove_image" value="1" class="form-check" x-model="remove"> Remove the current image</label>
                @endif
                <x-input name="featured_image_alt" label="Image description (alt text)" :value="$post->featured_image_alt" maxlength="160" help="What the image shows, for people using screen readers." class="mt-4"/>
            </section>

            <section class="card space-y-4 p-5" x-data="{ t: @js(old('meta_title', $post->meta_title) ?? ''), d: @js(old('meta_description', $post->meta_description) ?? '') }">
                <h2 class="text-lg">Search engines (SEO)</h2>
                <div>
                    <x-input name="meta_title" label="SEO title" :value="$post->meta_title" maxlength="70" x-model="t" help="Optional. Defaults to the post title."/>
                    <p class="mt-1 text-right text-xs text-slate-500" aria-hidden="true"><span x-text="t.length"></span>/70</p>
                </div>
                <div>
                    <x-input name="meta_description" type="textarea" rows="3" label="SEO description" :value="$post->meta_description" maxlength="160" x-model="d" help="Optional. Defaults to the summary."/>
                    <p class="mt-1 text-right text-xs text-slate-500" aria-hidden="true"><span x-text="d.length"></span>/160</p>
                </div>
            </section>

            @if ($editing)
                <section class="card border-rose-200 p-5">
                    <h2 class="text-lg">Delete post</h2>
                    <p class="mt-1 text-sm text-slate-600">This removes the post and its image for good.</p>
                    <button type="submit" form="delete-post" class="btn-danger btn-sm mt-3" data-testid="delete-post"><x-icon name="trash" class="size-4"/> Delete post</button>
                </section>
            @endif
        </aside>
    </form>

    @if ($editing)
        <form id="delete-post" method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post for good? This cannot be undone.')">
            @csrf @method('DELETE')
        </form>
    @endif
</x-layouts.admin>
