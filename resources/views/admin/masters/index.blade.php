<x-layouts.admin title="Lists & filters">
    <p class="mb-6 text-sm text-slate-600">These lists power the search filters and the lawyer registration form. Hidden items disappear from filters but stay on existing profiles.</p>
    <div class="grid gap-6 xl:grid-cols-3">
        @foreach ($lists as $type => $list)
            <section class="card flex flex-col">
                <h2 class="border-b border-slate-200 px-5 py-4 font-sans font-semibold">{{ $list['label'] }} <span class="text-sm font-normal text-slate-500">({{ $list['items']->count() }})</span></h2>
                <form method="POST" action="{{ route('admin.masters.store', $type) }}" class="flex gap-2 border-b border-slate-100 p-4">
                    @csrf
                    <input name="name" required maxlength="100" placeholder="Add new…" class="form-input py-2" aria-label="New {{ strtolower($list['label']) }} name">
                    <button class="btn-primary btn-sm">Add</button>
                </form>
                <ul class="max-h-[28rem] flex-1 divide-y divide-slate-100 overflow-y-auto text-sm">
                    @foreach ($list['items'] as $item)
                        <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                            <span class="{{ $item->is_active ? 'text-slate-800' : 'text-slate-400 line-through' }}">{{ $item->name }} <span class="text-xs text-slate-400">· {{ $item->lawyers_count }}</span></span>
                            <form method="POST" action="{{ route('admin.masters.toggle', [$type, $item->id]) }}">@csrf<button class="text-xs font-medium {{ $item->is_active ? 'text-slate-500 hover:text-rose-600' : 'text-emerald-700' }}">{{ $item->is_active ? 'Hide' : 'Show' }}</button></form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <section class="card mt-6 p-5">
        <h2 class="font-sans font-semibold">Cities</h2>
        <div class="mt-4 grid gap-6 lg:grid-cols-[320px_1fr]">
            <div>
                <form method="GET" class="flex gap-2">
                    <select name="state" class="form-input py-2" onchange="this.form.submit()" aria-label="State">
                        <option value="">Choose a state…</option>
                        @foreach ($states as $s)<option value="{{ $s->id }}" @selected($state?->id === $s->id)>{{ $s->name }} ({{ $s->cities_count }})</option>@endforeach
                    </select>
                </form>
                @if ($state)
                    <form method="POST" action="{{ route('admin.masters.cities.store') }}" class="mt-3 flex gap-2">
                        @csrf
                        <input type="hidden" name="state_id" value="{{ $state->id }}">
                        <input name="name" required maxlength="100" placeholder="New city in {{ $state->name }}" class="form-input py-2" aria-label="New city name">
                        <button class="btn-primary btn-sm">Add</button>
                    </form>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($cities as $c)<span class="chip">{{ $c->name }}</span>@endforeach
                @if ($state && $cities->isEmpty())<p class="text-sm text-slate-500">No cities yet.</p>@endif
            </div>
        </div>
    </section>
</x-layouts.admin>
