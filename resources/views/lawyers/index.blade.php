@php
    $f = $filters;
    $chips = [];
    $base = request()->except('page');
    $label = fn ($items, $slug) => optional(collect($items)->firstWhere('slug', $slug))->name ?? $slug;
    if ($f['q']) $chips['q'] = '“'.$f['q'].'”';
    if ($f['area']) $chips['area'] = $label($areas, $f['area']);
    if ($f['state']) $chips['state'] = $label($states, $f['state']);
    if ($f['city']) $chips['city'] = $label($cities, $f['city']);
    if ($f['experience']) $chips['experience'] = config('mat.experience_buckets')[$f['experience']];
    if ($f['language']) $chips['language'] = $label($languages, $f['language']);
    if ($f['court']) $chips['court'] = $label($courts, $f['court']);
    if ($f['gender']) $chips['gender'] = config('mat.genders')[$f['gender']].' lawyers';
    if ($f['online']) $chips['online'] = 'Online consultation';
    if ($f['fee_max'] !== null) $chips['fee_max'] = 'Fee up to ₹'.number_format($f['fee_max']);
@endphp
<x-layouts.app :title="$heading" :description="'Search verified '.strtolower($heading).' on MAT LEGAL NETWORK. Compare experience, courts and languages, then connect through our helpline.'"
               :canonical="route('lawyers.index', array_filter(['area' => $f['area'], 'state' => $f['state'], 'city' => $f['city']]))" :noindex="$lawyers->isEmpty()">
    <div class="border-b border-slate-200 bg-slate-50">
        <div class="container-page py-8">
            <nav class="text-xs text-slate-500" aria-label="Breadcrumb"><a href="{{ route('home') }}" class="hover:text-slate-700">Home</a> <span class="mx-1">/</span> <span class="text-slate-700">Find a lawyer</span></nav>
            <h1 class="mt-2 text-3xl font-bold text-brand-950 sm:text-4xl" data-testid="results-heading">{{ $heading }}</h1>
            <p class="mt-2 text-slate-600"><span data-testid="results-count">{{ number_format($lawyers->total()) }}</span> verified {{ Str::plural('lawyer', $lawyers->total()) }} found</p>
        </div>
    </div>

    <div class="container-page py-8" x-data="{ filtersOpen: false }">
        <div class="lg:grid lg:grid-cols-[280px_1fr] lg:gap-8">
            {{-- Filters --}}
            <aside>
                <div class="fixed inset-0 z-50 bg-slate-900/50 lg:hidden" x-show="filtersOpen" x-cloak x-transition.opacity @click="filtersOpen = false"></div>
                <form method="GET" action="{{ route('lawyers.index') }}" id="filters" data-testid="filters" @submit="[...$el.elements].forEach(e => { if (e.name && (e.value === '' || (e.name === 'sort' && e.value === 'relevance'))) e.disabled = true })"
                      class="fixed inset-y-0 right-0 z-50 flex w-[88%] max-w-sm flex-col bg-white shadow-2xl transition-transform lg:sticky lg:top-24 lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:bg-transparent lg:shadow-none lg:transition-none"
                      :class="filtersOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
                      x-data="cityPicker({ url: '{{ route('locations.cities', ':state') }}', state: @js($f['state']), city: @js($f['city']), key: 'slug', initial: @js($cities) })">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 lg:hidden">
                        <p class="text-lg font-semibold">Filters</p>
                        <button type="button" class="btn-ghost -mr-2" @click="filtersOpen = false" aria-label="Close filters"><x-icon name="x" class="size-5"/></button>
                    </div>
                    <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 lg:max-h-[calc(100vh-11rem)] lg:rounded-2xl lg:border lg:border-slate-200 lg:bg-white">
                        <input type="hidden" name="sort" value="{{ $f['sort'] }}">
                        <div>
                            <label for="q" class="form-label">Lawyer or firm name</label>
                            <div class="relative">
                                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"/>
                                <input id="q" type="search" name="q" value="{{ $f['q'] }}" maxlength="100" placeholder="e.g. Sharma" class="form-input pl-9">
                            </div>
                        </div>
                        <div>
                            <label for="area" class="form-label">Practice area</label>
                            <select id="area" name="area" class="form-input">
                                <option value="">All practice areas</option>
                                @foreach ($areas as $a)<option value="{{ $a->slug }}" @selected($f['area'] === $a->slug)>{{ $a->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="state" class="form-label">State</label>
                            <select id="state" name="state" x-model="state" class="form-input">
                                <option value="">All states</option>
                                @foreach ($states as $s)<option value="{{ $s->slug }}" @selected($f['state'] === $s->slug)>{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="city" class="form-label">City</label>
                            <select id="city" name="city" x-model="city" :disabled="!state" class="form-input disabled:bg-slate-50">
                                <option value="" x-text="loading ? 'Loading…' : (state ? 'All cities' : 'Select a state first')">All cities</option>
                                <template x-for="c in cities" :key="c.id"><option :value="c.slug" x-text="c.name" :selected="c.slug === city"></option></template>
                            </select>
                        </div>
                        <div>
                            <label for="experience" class="form-label">Experience</label>
                            <select id="experience" name="experience" class="form-input">
                                <option value="">Any experience</option>
                                @foreach (config('mat.experience_buckets') as $y => $l)<option value="{{ $y }}" @selected($f['experience'] === $y)>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="language" class="form-label">Language</label>
                            <select id="language" name="language" class="form-input">
                                <option value="">Any language</option>
                                @foreach ($languages as $l)<option value="{{ $l->slug }}" @selected($f['language'] === $l->slug)>{{ $l->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="court" class="form-label">Court</label>
                            <select id="court" name="court" class="form-input">
                                <option value="">Any court</option>
                                @foreach ($courts as $c)<option value="{{ $c->slug }}" @selected($f['court'] === $c->slug)>{{ $c->name }}</option>@endforeach
                            </select>
                        </div>
                        <fieldset>
                            <legend class="form-label">Gender</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['' => 'Any'] + config('mat.genders') as $k => $l)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="gender" value="{{ $k }}" class="peer sr-only" @checked((string) $f['gender'] === (string) $k)>
                                        <span class="inline-block rounded-full border border-slate-300 px-3 py-1 text-sm text-slate-700 peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ $l }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div>
                            <label for="fee_max" class="form-label">Maximum consultation fee (₹)</label>
                            <select id="fee_max" name="fee_max" class="form-input">
                                <option value="">Any fee</option>
                                @foreach ([500, 1000, 2000, 3000, 5000] as $fee)<option value="{{ $fee }}" @selected($f['fee_max'] === $fee)>Up to ₹{{ number_format($fee) }}</option>@endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="online" value="1" class="form-check" @checked($f['online'])> Offers online consultation
                        </label>
                    </div>
                    <div class="flex gap-2 border-t border-slate-200 px-5 py-4 lg:mt-3 lg:border-0 lg:px-0 lg:py-0">
                        <a href="{{ route('lawyers.index') }}" class="btn-outline flex-1">Clear all</a>
                        <button class="btn-primary flex-1" data-testid="apply-filters">Apply filters</button>
                    </div>
                </form>
            </aside>

            {{-- Results --}}
            <section aria-labelledby="results-heading" class="mt-6 min-w-0 lg:mt-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" class="btn-outline lg:hidden" @click="filtersOpen = true" data-testid="open-filters">
                        <x-icon name="filter" class="size-4"/> Filters @if ($activeFilterCount)<span class="rounded-full bg-brand-700 px-1.5 text-xs text-white">{{ $activeFilterCount }}</span>@endif
                    </button>
                    <form method="GET" action="{{ route('lawyers.index') }}" class="ml-auto flex items-center gap-2">
                        @foreach (collect($base)->except('sort') as $k => $v)
                            @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        <label for="sort" class="text-sm text-slate-600">Sort by</label>
                        <select id="sort" name="sort" class="form-input w-auto py-2" onchange="this.form.submit()">
                            @foreach ($sorts as $k => $l)<option value="{{ $k }}" @selected($f['sort'] === $k)>{{ $l }}</option>@endforeach
                        </select>
                        <noscript><button class="btn-outline btn-sm">Go</button></noscript>
                    </form>
                </div>

                @if ($chips)
                    <div class="mt-4 flex flex-wrap items-center gap-2" data-testid="active-filters">
                        @foreach ($chips as $key => $text)
                            @php $without = collect($base)->except(array_merge([$key], $key === 'state' ? ['city'] : []))->all(); @endphp
                            <a href="{{ route('lawyers.index', $without) }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 py-1 pr-2 pl-3 text-sm text-brand-800 ring-1 ring-brand-100 hover:bg-brand-100" aria-label="Remove filter {{ $text }}">
                                {{ $text }} <x-icon name="x" class="size-3.5"/>
                            </a>
                        @endforeach
                        <a href="{{ route('lawyers.index') }}" class="text-sm font-medium text-slate-500 underline-offset-2 hover:text-slate-800 hover:underline">Clear all</a>
                    </div>
                @endif

                @if ($lawyers->isEmpty())
                    <div class="card mt-6 px-6 py-14 text-center" data-testid="empty-state">
                        <span class="mx-auto inline-flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-500"><x-icon name="search" class="size-6"/></span>
                        <h2 class="mt-4 text-xl font-semibold text-slate-900">No lawyers match these filters yet</h2>
                        <p class="mx-auto mt-2 max-w-md text-slate-600">Try removing a filter or searching a nearby city. Or call us – our team can often find the right lawyer even when they are not listed here yet.</p>
                        <div class="mt-6 flex flex-col items-center gap-3">
                            <a href="{{ route('lawyers.index') }}" class="btn-outline">Clear filters</a>
                            <x-contact-buttons class="justify-center"/>
                        </div>
                    </div>
                @else
                    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3" data-testid="results">
                        @foreach ($lawyers as $lawyer)
                            <x-lawyer-card :lawyer="$lawyer"/>
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $lawyers->onEachSide(1)->links() }}</div>
                @endif

                <div class="mt-10 flex flex-col items-start gap-4 rounded-2xl border border-brand-100 bg-brand-50 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-brand-950">Talk to us before you decide</p>
                        <p class="text-sm text-slate-600">Quote the lawyer's Profile ID and we'll connect you. Lawyers' personal numbers are never shown.</p>
                    </div>
                    <x-contact-buttons/>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
