<x-layouts.admin title="Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Pending approval', $counts['pending'], route('admin.lawyers.index', ['status' => 'pending']), 'bg-amber-50 text-amber-700'],
            ['Live lawyers', $counts['approved'], route('admin.lawyers.index', ['status' => 'approved']), 'bg-emerald-50 text-emerald-700'],
            ['New enquiries', $counts['new_enquiries'], route('admin.enquiries.index', ['status' => 'new']), 'bg-sky-50 text-sky-700'],
            ['Unreviewed profile changes', $counts['unreviewed_changes'], route('admin.changes.index'), 'bg-indigo-50 text-indigo-700'],
        ] as [$label, $value, $href, $tone])
            <a href="{{ $href }}" class="card block p-5 hover:border-brand-200 hover:shadow-md" data-testid="stat">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 flex items-baseline gap-2"><span class="font-display text-3xl font-bold text-slate-900">{{ number_format($value) }}</span></p>
                <span class="mt-3 inline-block rounded-md px-2 py-0.5 text-xs font-semibold {{ $tone }}">View →</span>
            </a>
        @endforeach
    </div>
    <p class="mt-3 text-sm text-slate-500">{{ $counts['rejected'] }} awaiting lawyer changes · {{ $counts['suspended'] }} suspended · {{ $counts['enquiries_week'] }} enquiries in the last 7 days</p>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="font-sans font-semibold">Oldest pending registrations</h2>
                <a href="{{ route('admin.lawyers.index') }}" class="text-sm font-medium text-brand-700">View all</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($pending as $l)
                    <li><a href="{{ route('admin.lawyers.show', $l) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                        <span class="min-w-0"><span class="block truncate font-medium text-slate-900">{{ $l->full_name }}</span><span class="text-xs text-slate-500">{{ $l->locationLabel() }} · {{ $l->public_code }}</span></span>
                        <span class="shrink-0 text-xs text-slate-500">{{ $l->submitted_at?->diffForHumans() }}</span>
                    </a></li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">No pending registrations. 🎉</li>
                @endforelse
            </ul>
        </section>
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="font-sans font-semibold">Latest enquiries</h2>
                <a href="{{ route('admin.enquiries.index') }}" class="text-sm font-medium text-brand-700">View all</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($enquiries as $e)
                    <li><a href="{{ route('admin.enquiries.show', $e) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                        <span class="min-w-0"><span class="block truncate font-medium text-slate-900">{{ $e->name }} <span class="font-normal text-slate-500">· {{ $e->phone }}</span></span><span class="text-xs text-slate-500">{{ $e->lawyer?->full_name ?? 'General enquiry' }} · {{ $e->reference }}</span></span>
                        <span class="badge shrink-0 {{ $e->status->badgeClasses() }}">{{ $e->status->label() }}</span>
                    </a></li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">No enquiries yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
