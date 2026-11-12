@php $tabs = ['all' => 'All', 'draft' => 'Drafts', 'published' => 'Published']; @endphp
<x-layouts.admin :title="$showAll ? 'Blog posts' : 'My blog posts'">
    <x-slot:actions>
        <a href="{{ route('admin.posts.create') }}" class="btn-primary" data-testid="new-post"><x-icon name="plus" class="size-4"/> <span class="max-sm:sr-only">New post</span></a>
    </x-slot:actions>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-1 rounded-full bg-white p-1 shadow-pop ring-1 ring-slate-200/70" aria-label="Status">
            @foreach ($tabs as $k => $l)
                <a href="{{ route('admin.posts.index', array_filter(['status' => $k, 'q' => request('q'), 'author' => $author ?: null])) }}" @if ($status === $k) aria-current="page" @endif class="rounded-full px-3.5 py-1.5 text-sm font-bold {{ $status === $k ? 'bg-ink text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $l }} <span class="ml-1 text-xs opacity-70">{{ $k === 'all' ? $counts->sum() : ($counts[$k] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>
        <form method="GET" class="flex w-full flex-wrap gap-2 sm:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            @if ($showAll)
                <label for="author" class="sr-only">Author</label>
                <select id="author" name="author" class="form-input w-full rounded-full py-2 sm:w-44" onchange="this.form.submit()">
                    <option value="">All authors</option>
                    @foreach ($authors as $a)<option value="{{ $a->id }}" @selected($author === $a->id)>{{ $a->name }}</option>@endforeach
                </select>
            @endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search titles" class="form-input min-w-0 flex-1 rounded-full py-2 sm:w-60" aria-label="Search posts by title">
            <button class="btn-outline">Search</button>
        </form>
    </div>

    <div class="card mt-5 overflow-x-auto">
        <table class="admin-table" data-testid="posts-table">
            <thead><tr><th>Title</th>@if ($showAll)<th>Author</th>@endif<th>Category</th><th>Status</th><th>Updated</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($posts as $p)
                    <tr data-row-href="{{ route('admin.posts.edit', $p) }}" data-testid="post-row" class="clickable-row">
                        <td class="min-w-56"><a href="{{ route('admin.posts.edit', $p) }}" class="row-link font-medium text-slate-900">{{ $p->title }}</a>
                            <p class="text-xs text-slate-500">/blog/{{ $p->slug }}</p></td>
                        @if ($showAll)<td class="whitespace-nowrap text-slate-600">{{ $p->authorName() }}</td>@endif
                        <td class="text-slate-600">{{ $p->category?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$p->status"/>@if ($p->isPublished() && $p->published_at)<p class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $p->published_at->format('d M Y') }}</p>@endif</td>
                        <td class="whitespace-nowrap text-slate-500">{{ $p->updated_at->format('d M Y, H:i') }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if ($p->isPublished())
                                <a href="{{ route('blog.show', $p) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">View<span class="sr-only"> {{ $p->title }} on the blog (opens in a new tab)</span></a>
                            @else
                                <a href="{{ route('admin.posts.preview', $p) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">Preview<span class="sr-only"> {{ $p->title }} (opens in a new tab)</span></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $showAll ? 6 : 5 }}" class="py-12 text-center text-slate-500">
                        @if (request('q') || $status !== 'all' || $author) No posts match. @else No posts yet. <a href="{{ route('admin.posts.create') }}" class="font-bold text-brand-700 underline">Write the first one</a>. @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $posts->links() }}</div>
</x-layouts.admin>
