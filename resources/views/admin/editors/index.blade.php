<x-layouts.admin title="Blog editors">
    <x-slot:actions>
        <a href="{{ route('admin.editors.create') }}" class="btn-primary" data-testid="new-editor"><x-icon name="plus" class="size-4"/> <span class="max-sm:sr-only">Add editor</span></a>
    </x-slot:actions>

    <p class="max-w-3xl text-sm text-slate-600">Editors (authors) can sign in and write, publish and delete <strong class="text-ink">their own blog posts only</strong>. They cannot see lawyers, enquiries, settings or anything else in the admin panel.</p>

    <form method="GET" class="mt-5 flex max-w-md gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Name or email" class="form-input min-w-0 flex-1 rounded-full py-2" aria-label="Search editors">
        <button class="btn-outline">Search</button>
    </form>

    <div class="card mt-5 overflow-x-auto">
        <table class="admin-table" data-testid="editors-table">
            <thead><tr><th>Editor</th><th>Posts</th><th>Status</th><th>Last sign-in</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($editors as $e)
                    <tr data-row-href="{{ route('admin.editors.edit', $e) }}" class="clickable-row" data-testid="editor-row">
                        <td><a href="{{ route('admin.editors.edit', $e) }}" class="row-link font-medium text-slate-900">{{ $e->name }}</a><p class="text-xs text-slate-500">{{ $e->email }}</p></td>
                        <td class="whitespace-nowrap text-slate-600">{{ $e->published_count }} published · {{ $e->posts_count - $e->published_count }} drafts</td>
                        <td><span class="badge {{ $e->is_active ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-300' }}">{{ $e->is_active ? 'Active' : 'Deactivated' }}</span></td>
                        <td class="whitespace-nowrap text-slate-500">{{ $e->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</td>
                        <td class="text-right"><a href="{{ route('admin.posts.index', ['author' => $e->id]) }}" class="btn-outline btn-sm">Posts<span class="sr-only"> by {{ $e->name }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-slate-500">No editors yet. <a href="{{ route('admin.editors.create') }}" class="font-bold text-brand-700 underline">Add the first one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $editors->links() }}</div>
</x-layouts.admin>
