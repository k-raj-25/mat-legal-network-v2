<x-layouts.admin title="Blog categories">
    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="card overflow-x-auto">
            <table class="admin-table" data-testid="categories-table">
                <thead><tr><th>Category</th><th>Posts</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($categories as $c)
                        <tr>
                            <td><p class="font-medium text-slate-900">{{ $c->name }}</p><p class="text-xs text-slate-500">/blog/category/{{ $c->slug }}</p></td>
                            <td class="text-slate-600">{{ number_format($c->posts_count) }}</td>
                            <td><span class="badge {{ $c->is_active ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-300' }}">{{ $c->is_active ? 'Shown' : 'Hidden' }}</span></td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.blog-categories.toggle', $c) }}">@csrf
                                    <button class="btn-outline btn-sm">{{ $c->is_active ? 'Hide' : 'Show' }}<span class="sr-only"> {{ $c->name }}</span></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-slate-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <section class="card self-start p-5">
            <h2 class="text-lg">Add a category</h2>
            <p class="mt-1 text-sm text-slate-600">Editors pick from shown categories. Hidden categories keep their posts but disappear from the blog menu.</p>
            <form method="POST" action="{{ route('admin.blog-categories.store') }}" class="mt-4 space-y-3">
                @csrf
                <x-input name="name" label="Name" required maxlength="80" placeholder="e.g. Consumer rights"/>
                <button class="btn-primary">Add category</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
