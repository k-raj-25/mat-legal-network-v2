<x-layouts.admin title="Enquiries">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-1 rounded-full bg-white p-1 shadow-pop ring-1 ring-slate-200/70" aria-label="Status">
            <a href="{{ route('admin.enquiries.index') }}" class="rounded-full px-3.5 py-1.5 text-sm font-bold {{ ! $status ? 'bg-ink text-white' : 'text-slate-600 hover:bg-slate-50' }}">All <span class="text-xs opacity-70">{{ $counts->sum() }}</span></a>
            @foreach (\App\Enums\EnquiryStatus::cases() as $s)
                @php($n = (int) ($counts[$s->value] ?? 0))
                @php($waiting = $s === \App\Enums\EnquiryStatus::SuspectedSpam && $n > 0 && $status !== $s->value)
                <a href="{{ route('admin.enquiries.index', ['status' => $s->value]) }}" @if ($s === \App\Enums\EnquiryStatus::SuspectedSpam) data-testid="tab-suspected-spam" @endif
                   class="rounded-full px-3.5 py-1.5 text-sm font-bold {{ $status === $s->value ? 'bg-ink text-white' : ($waiting ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'text-slate-600 hover:bg-slate-50') }}">{{ $s->label() }} <span class="text-xs opacity-70">{{ $n }}</span></a>
            @endforeach
        </nav>
        <form method="GET" class="flex gap-2">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, phone, email, reference" class="form-input w-64 rounded-full py-2" aria-label="Search enquiries">
            <button class="btn-outline">Search</button>
        </form>
    </div>
    @if ($status === \App\Enums\EnquiryStatus::SuspectedSpam->value)
        @include('admin.enquiries._spam-queue')
    @else
    <div class="card mt-5 overflow-x-auto">
        <table class="admin-table" data-testid="enquiries-table">
            <thead><tr><th>Client</th><th>Regarding</th><th>Message</th><th>Status</th><th>Received</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($enquiries as $e)
                    <tr>
                        <td><p class="font-medium text-slate-900">{{ $e->name }}</p><p class="text-xs text-slate-500">{{ $e->phone }} · prefers {{ $e->preferred_contact }}</p></td>
                        <td class="text-slate-600">{{ $e->lawyer ? $e->lawyer->full_name.' ('.$e->lawyer->public_code.')' : ($e->practiceArea?->name ?? 'General') }}</td>
                        <td class="max-w-xs text-slate-600">{{ Str::limit($e->message, 90) }}</td>
                        <td><span class="badge {{ $e->status->badgeClasses() }}">{{ $e->status->label() }}</span>@if ($e->spam_check === 'pending')<span class="mt-1 block text-[11px] text-slate-400">AI check running…</span>@endif</td>
                        <td class="whitespace-nowrap text-slate-500">{{ $e->created_at->format('d M Y, H:i') }}</td>
                        <td class="text-right"><a href="{{ route('admin.enquiries.show', $e) }}" class="btn-outline btn-sm">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-slate-500">No enquiries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
    <div class="mt-6">{{ $enquiries->links() }}</div>
</x-layouts.admin>
