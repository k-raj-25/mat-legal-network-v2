@php $labels = \App\Services\LawyerProfileService::FIELD_LABELS + ['status' => 'Status']; @endphp
<dl class="mt-2 grid gap-2 text-sm">
    @foreach ($changes as $field => $c)
        <div class="grid gap-1 sm:grid-cols-[150px_1fr]">
            <dt class="font-medium text-slate-700">{{ $labels[$field] ?? $field }}</dt>
            <dd class="min-w-0 break-words"><span class="rounded bg-rose-50 px-1 text-rose-800 line-through decoration-rose-300">{{ Str::limit((string) $c['old'], 300) }}</span> <span class="text-slate-400">→</span> <span class="rounded bg-emerald-50 px-1 text-emerald-800">{{ Str::limit((string) $c['new'], 300) }}</span></dd>
        </div>
    @endforeach
</dl>
