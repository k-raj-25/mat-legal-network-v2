<x-layouts.admin title="Profile changes">
    <x-slot:actions>
        @unless ($showAll)
            <form method="POST" action="{{ route('admin.changes.review-all') }}">@csrf<button class="btn-outline btn-sm">Mark all as reviewed</button></form>
        @endunless
    </x-slot:actions>
    <p class="mb-4 text-sm text-slate-600">Approved lawyers' edits go live immediately. Review them here and suspend a profile if something looks wrong.
        <a href="{{ route('admin.changes.index', $showAll ? [] : ['all' => 1]) }}" class="font-medium text-brand-700">{{ $showAll ? 'Show unreviewed only' : 'Show all history' }}</a></p>
    <div class="space-y-4" data-testid="change-logs">
        @forelse ($logs as $log)
            <article class="card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <a href="{{ route('admin.lawyers.show', $log->lawyer->slug) }}" class="font-semibold text-slate-900 hover:underline">{{ $log->lawyer->full_name }}</a>
                        <span class="text-sm text-slate-500">· {{ $log->lawyer->public_code }} · {{ $log->created_at->format('d M Y, H:i') }} · {{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    @if ($log->reviewed_at)
                        <span class="text-xs text-slate-500">Reviewed {{ $log->reviewed_at->format('d M Y') }}</span>
                    @else
                        <form method="POST" action="{{ route('admin.changes.review', $log) }}">@csrf<button class="btn-outline btn-sm">Mark reviewed</button></form>
                    @endif
                </div>
                @include('admin.changes._diff', ['changes' => $log->changes])
            </article>
        @empty
            <div class="card px-6 py-12 text-center text-slate-500">Nothing to review. 🎉</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-layouts.admin>
