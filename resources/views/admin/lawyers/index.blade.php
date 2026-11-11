@php $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Changes requested', 'suspended' => 'Suspended', 'all' => 'All']; @endphp
<x-layouts.admin title="Lawyers">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-1 rounded-full bg-white p-1 shadow-pop ring-1 ring-slate-200/70" aria-label="Status">
            @foreach ($tabs as $k => $l)
                <a href="{{ route('admin.lawyers.index', ['status' => $k, 'q' => request('q')]) }}" class="rounded-full px-3.5 py-1.5 text-sm font-bold {{ $status === $k ? 'bg-ink text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $l }} <span class="ml-1 text-xs opacity-70">{{ $k === 'all' ? $counts->sum() : ($counts[$k] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, phone, enrolment no., ID" class="form-input w-72 rounded-full py-2" aria-label="Search lawyers">
            <button class="btn-outline">Search</button>
        </form>
    </div>

    <div class="card mt-5 overflow-x-auto">
        <table class="admin-table" data-testid="lawyers-table">
            <thead><tr><th>Lawyer</th><th>Location</th><th>Enrolment</th><th>Status</th><th>{{ $status === 'pending' ? 'Submitted' : 'Updated' }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($lawyers as $l)
                    <tr>
                        <td><p class="font-medium text-slate-900">{{ $l->full_name }}</p><p class="text-xs text-slate-500">{{ $l->user->email }} · {{ $l->public_code }}</p></td>
                        <td class="text-slate-600">{{ $l->locationLabel() }}</td>
                        <td class="text-slate-600"><p>{{ $l->enrolment_number }}</p><p class="text-xs text-slate-400">since {{ $l->practice_since }}</p></td>
                        <td><x-status-badge :status="$l->status"/></td>
                        <td class="whitespace-nowrap text-slate-500">{{ ($status === 'pending' ? $l->submitted_at : $l->updated_at)?->format('d M Y, H:i') }}</td>
                        <td class="text-right"><a href="{{ route('admin.lawyers.show', $l) }}" class="btn-outline btn-sm">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-slate-500">No lawyers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $lawyers->links() }}</div>
</x-layouts.admin>
