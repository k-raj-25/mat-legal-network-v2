{{-- AI spam check result for one enquiry, plus the review buttons while it is held back. --}}
@php($e = $enquiry)
@php($suspected = $e->status === \App\Enums\EnquiryStatus::SuspectedSpam)
<section class="card p-5 {{ $suspected ? 'ring-2 ring-amber-300' : '' }}" data-testid="spam-panel">
    <div class="flex items-center justify-between gap-3">
        <h3 class="flex items-center gap-2 text-lg"><x-icon name="sparkles" class="size-5 text-amber-600"/> AI spam check</h3>
        @switch($e->spam_check)
            @case('flagged') <span class="badge bg-amber-50 text-amber-800 ring-amber-200">Flagged</span> @break
            @case('passed') <span class="badge bg-emerald-50 text-emerald-800 ring-emerald-200">Looks genuine</span> @break
            @case('pending') <span class="badge bg-slate-100 text-slate-700 ring-slate-300">Checking…</span> @break
            @case('failed') <span class="badge bg-slate-100 text-slate-700 ring-slate-300">Not checked</span> @break
            @default <span class="badge bg-slate-100 text-slate-700 ring-slate-300">Off</span>
        @endswitch
    </div>

    @if (in_array($e->spam_check, ['flagged', 'passed'], true))
        <div class="mt-3">@include('admin.enquiries._spam-meter', ['e' => $e])</div>
        <p class="mt-2 text-sm break-words text-slate-700" data-testid="spam-reason">{{ $e->spam_reason }}</p>
        <p class="mt-2 text-xs text-slate-400">Checked {{ $e->spam_checked_at?->format('d M Y, H:i') }}{{ $e->spam_model ? ' · '.$e->spam_model : '' }}</p>
    @elseif ($e->spam_check === 'pending')
        <p class="mt-2 text-sm text-slate-600">The check runs in the background and usually takes a few seconds. Until then this is a normal enquiry.</p>
    @elseif ($e->spam_check === 'failed')
        <p class="mt-2 text-sm text-slate-600">The AI service could not be reached, so this enquiry was handled as normal.</p>
    @else
        <p class="mt-2 text-sm text-slate-600">AI screening was not switched on when this enquiry arrived.</p>
    @endif

    @if ($e->spam_review)
        <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600" data-testid="spam-review">
            {{ $e->spam_review === 'confirmed' ? 'Confirmed as spam' : 'Marked not spam' }} by {{ $e->spamReviewer?->name ?? 'an admin' }}, {{ $e->spam_reviewed_at?->format('d M Y, H:i') }}
        </p>
    @endif

    @if ($suspected)
        <p class="mt-4 text-sm text-slate-700">Held back for your review. No alert was sent and it is not assigned to anyone.</p>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <form method="POST" action="{{ route('admin.enquiries.spam-review') }}">
                @csrf <input type="hidden" name="ids[]" value="{{ $e->id }}">
                <button name="decision" value="not_spam" class="btn-outline w-full" data-testid="not-spam">Not spam</button>
            </form>
            <form method="POST" action="{{ route('admin.enquiries.spam-review') }}">
                @csrf <input type="hidden" name="ids[]" value="{{ $e->id }}">
                <button name="decision" value="spam" class="btn-danger w-full" data-testid="confirm-spam">Confirm spam</button>
            </form>
        </div>
    @endif
</section>
