@props(['lawyer'])
<article class="card group relative flex h-full flex-col p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md" data-testid="lawyer-card">
    <div class="flex gap-4">
        <x-avatar :lawyer="$lawyer"/>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <h3 class="truncate font-sans text-base font-semibold text-slate-900">
                    <a href="{{ route('lawyers.show', $lawyer->slug) }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $lawyer->full_name }}</a>
                </h3>
                @if ($lawyer->is_featured)
                    <span class="badge shrink-0 bg-gold-50 text-gold-700 ring-gold-200"><x-icon name="star" class="size-3"/> Featured</span>
                @endif
            </div>
            <p class="mt-0.5 text-sm text-slate-500">{{ $lawyer->designation ?? 'Advocate' }}@if ($lawyer->firm_name) · {{ $lawyer->firm_name }}@endif</p>
            <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-600"><x-icon name="map-pin" class="size-4 text-slate-400"/> {{ $lawyer->locationLabel() }}</p>
            <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-600"><x-icon name="briefcase" class="size-4 text-slate-400"/> {{ $lawyer->experienceLabel() }} experience</p>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-1.5">
        @foreach ($lawyer->practiceAreas->take(3) as $area)
            <span class="chip">{{ $area->name }}</span>
        @endforeach
        @if ($lawyer->practiceAreas->count() > 3)
            <span class="chip bg-slate-50 text-slate-600 ring-slate-200">+{{ $lawyer->practiceAreas->count() - 3 }} more</span>
        @endif
    </div>
    <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
        <span class="text-slate-500">
            @if ($lawyer->offers_online_consultation)<span class="inline-flex items-center gap-1 text-emerald-700"><x-icon name="video" class="size-4"/> Online consult</span>@else In-person @endif
        </span>
        <span class="inline-flex items-center gap-1 font-semibold text-brand-700 group-hover:text-brand-900">View profile <x-icon name="arrow-right" class="size-4"/></span>
    </div>
</article>
