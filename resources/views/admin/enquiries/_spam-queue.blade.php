{{-- "Suspected spam" tab: held back by the AI check, waiting for an admin decision. Card layout so it works on phones too. --}}
<div class="mt-5 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200" data-testid="spam-queue-help">
    The AI check held these back: no new-enquiry alert was sent and nobody has acted on them. <strong>Confirm spam</strong> to file them away, or <strong>Not spam</strong> to move them to New. Nothing is deleted.
</div>

@if ($enquiries->isNotEmpty())
    <form id="bulk-spam" method="POST" action="{{ route('admin.enquiries.spam-review') }}"
          x-data="{ n: 0, sync() { this.n = document.querySelectorAll('input[form=bulk-spam][name=\'ids[]\']:checked').length } }"
          @change.window="sync()"
          class="mt-4 flex flex-wrap items-center gap-2 rounded-2xl bg-white p-3 shadow-pop ring-1 ring-slate-200/70" data-testid="spam-bulk">
        @csrf
        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="checkbox" class="form-check" data-testid="spam-select-all"
                   @change="document.querySelectorAll('input[form=bulk-spam][name=\'ids[]\']').forEach(b => b.checked = $event.target.checked); sync()">
            Select all on this page
        </label>
        <span class="text-xs text-slate-500" x-text="n ? n + ' selected' : ''"></span>
        <span class="ml-auto flex flex-wrap gap-2">
            <button name="decision" value="not_spam" class="btn-outline btn-sm" :disabled="!n" :class="!n && 'opacity-50'" data-testid="bulk-not-spam">Not spam</button>
            <button name="decision" value="spam" class="btn-danger btn-sm" :disabled="!n" :class="!n && 'opacity-50'" data-testid="bulk-confirm-spam">Confirm spam</button>
        </span>
    </form>
@endif

<ul class="mt-4 space-y-3" data-testid="spam-queue">
    @forelse ($enquiries as $e)
        <li class="card p-4 sm:p-5" data-testid="spam-item">
            <div class="flex items-start gap-3">
                <input type="checkbox" name="ids[]" value="{{ $e->id }}" form="bulk-spam" class="form-check mt-1 shrink-0" aria-label="Select {{ $e->reference }}">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                        <p class="min-w-0 font-semibold text-slate-900"><span class="break-words">{{ $e->name }}</span> <span class="font-mono text-xs font-normal text-slate-500">{{ $e->reference }}</span></p>
                        @include('admin.enquiries._spam-meter', ['e' => $e])
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $e->lawyer ? $e->lawyer->full_name.' ('.$e->lawyer->public_code.')' : ($e->practiceArea?->name ?? 'General') }} · {{ $e->created_at->format('d M Y, H:i') }}</p>
                    <p class="mt-2 text-sm break-words text-slate-700">{{ Str::limit($e->message, 220) }}</p>
                    <p class="mt-2 flex gap-1.5 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-700"><x-icon name="sparkles" class="mt-px size-4 shrink-0 text-amber-600"/><span><span class="font-semibold">AI reason:</span> {{ $e->spam_reason }}</span></p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.enquiries.show', $e) }}" class="btn-ghost btn-sm">Open</a>
                        <form method="POST" action="{{ route('admin.enquiries.spam-review') }}" class="ml-auto">
                            @csrf <input type="hidden" name="ids[]" value="{{ $e->id }}">
                            <button name="decision" value="not_spam" class="btn-outline btn-sm" data-testid="not-spam">Not spam</button>
                        </form>
                        <form method="POST" action="{{ route('admin.enquiries.spam-review') }}">
                            @csrf <input type="hidden" name="ids[]" value="{{ $e->id }}">
                            <button name="decision" value="spam" class="btn-danger btn-sm" data-testid="confirm-spam">Confirm spam</button>
                        </form>
                    </div>
                </div>
            </div>
        </li>
    @empty
        <li class="card py-12 text-center text-sm text-slate-500" data-testid="spam-queue-empty">No suspected spam waiting for review.</li>
    @endforelse
</ul>
