{{-- AI confidence that the enquiry is spam. Expects $e. --}}
@php($c = (int) $e->spam_confidence)
<span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600" title="AI confidence that this is spam">
    <span class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200" aria-hidden="true"><span class="block h-full rounded-full {{ $c >= 90 ? 'bg-rose-500' : ($c >= 70 ? 'bg-amber-500' : 'bg-slate-400') }}" style="width: {{ $c }}%"></span></span>
    {{ $c }}% spam
</span>
