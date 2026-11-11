@props(['lawyer'])
@php use App\Support\AreaStyle; @endphp
<article class="group relative flex h-full flex-col rounded-3xl border border-slate-200/80 bg-white p-5 shadow-pop transition duration-200 hover:-translate-y-1 hover:shadow-pop-lg" data-testid="lawyer-card">
    <div class="flex items-start gap-4">
        <x-avatar :lawyer="$lawyer"/>
        <div class="min-w-0 flex-1 pt-0.5">
            <h3 class="truncate text-lg leading-tight">
                <a href="{{ route('lawyers.show', $lawyer->slug) }}" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-none">{{ $lawyer->full_name }}</a>
            </h3>
            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $lawyer->designation ?? 'Advocate' }}@if ($lawyer->firm_name) · {{ $lawyer->firm_name }}@endif</p>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <span class="badge bg-brand-50 text-brand-700 ring-brand-100"><x-icon name="check" class="size-3"/> Verified</span>
                @if ($lawyer->is_featured)<span class="badge bg-amber-50 text-amber-700 ring-amber-200"><x-icon name="star" class="size-3"/> Featured</span>@endif
            </div>
        </div>
    </div>

    <dl class="mt-5 grid grid-cols-2 gap-2 text-sm">
        <div class="rounded-2xl bg-slate-50 px-3 py-2.5"><dt class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Experience</dt><dd class="mt-0.5 font-bold text-ink">{{ $lawyer->experienceLabel() }}</dd></div>
        <div class="min-w-0 rounded-2xl bg-slate-50 px-3 py-2.5"><dt class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Location</dt><dd class="mt-0.5 truncate font-bold text-ink">{{ $lawyer->city?->name }}</dd></div>
    </dl>

    <div class="mt-4 flex flex-wrap gap-1.5">
        @foreach ($lawyer->practiceAreas->take(3) as $area)
            @php [, $tone] = AreaStyle::for($area->slug); @endphp
            <span class="chip {{ $tone }}">{{ Str::before($area->name, ' (') }}</span>
        @endforeach
        @if ($lawyer->practiceAreas->count() > 3)
            <span class="chip bg-slate-100 text-slate-600">+{{ $lawyer->practiceAreas->count() - 3 }}</span>
        @endif
    </div>

    <div class="mt-auto flex items-center justify-between pt-5 text-sm">
        <span class="font-semibold text-slate-500">
            @if ($lawyer->offers_online_consultation)<span class="inline-flex items-center gap-1.5 text-brand-700"><x-icon name="video" class="size-4"/> Online consult</span>@else In-person @endif
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-ink px-3.5 py-1.5 text-xs font-bold text-white transition group-hover:bg-coral-600">View profile <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-0.5"/></span>
    </div>
</article>
