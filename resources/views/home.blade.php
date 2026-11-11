<x-layouts.app>
    {{-- Hero + search --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 via-white to-white">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[480px] bg-[radial-gradient(60%_60%_at_70%_10%,rgba(219,185,107,0.18),transparent_70%)]"></div>
        <div class="container-page relative pt-14 pb-16 sm:pt-20 lg:pb-24">
            <div class="mx-auto max-w-3xl text-center">
                <p class="inline-flex items-center gap-2 rounded-full border border-gold-200 bg-white px-3 py-1 text-xs font-semibold text-gold-700 shadow-xs"><x-icon name="shield" class="size-4"/> Every lawyer is verified by our team</p>
                <h1 class="mt-5 text-4xl font-bold text-brand-950 sm:text-5xl lg:text-6xl">Find the right lawyer,<br class="hidden sm:block"> <span class="text-gold-600">without the runaround.</span></h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-600">Search advocates by city, practice area, language and experience. When you're ready, one call to MAT LEGAL NETWORK connects you – your number is never shared publicly.</p>
            </div>

            <form action="{{ route('lawyers.index') }}" method="GET" class="mx-auto mt-10 max-w-4xl rounded-2xl border border-slate-200 bg-white p-3 shadow-lg shadow-brand-900/5"
                  x-data="cityPicker({ url: '{{ route('locations.cities', ':state') }}', key: 'slug' })" data-testid="hero-search"
                  @submit="[...$el.elements].forEach(e => { if (e.name && e.value === '') e.disabled = true })">
                <div class="grid gap-2 md:grid-cols-[1.3fr_1fr_1fr_auto]">
                    <label class="sr-only" for="hero-area">Practice area</label>
                    <select id="hero-area" name="area" class="form-input border-0 bg-slate-50 py-3 shadow-none">
                        <option value="">All practice areas</option>
                        @foreach ($areas as $a)<option value="{{ $a->slug }}">{{ $a->name }}</option>@endforeach
                    </select>
                    <label class="sr-only" for="hero-state">State</label>
                    <select id="hero-state" name="state" x-model="state" class="form-input border-0 bg-slate-50 py-3 shadow-none">
                        <option value="">Any state</option>
                        @foreach ($states as $s)<option value="{{ $s->slug }}">{{ $s->name }}</option>@endforeach
                    </select>
                    <label class="sr-only" for="hero-city">City</label>
                    <select id="hero-city" name="city" x-model="city" :disabled="!state || loading" class="form-input border-0 bg-slate-50 py-3 shadow-none disabled:opacity-60">
                        <option value="" x-text="loading ? 'Loading…' : (state ? 'Any city' : 'Select state first')">Any city</option>
                        <template x-for="c in cities" :key="c.id"><option :value="c.slug" x-text="c.name"></option></template>
                    </select>
                    <button class="btn-primary py-3 md:px-6"><x-icon name="search" class="size-4"/> Search</button>
                </div>
                <div class="mt-2 flex items-center gap-2 px-1">
                    <x-icon name="user" class="size-4 text-slate-400"/>
                    <label class="sr-only" for="hero-q">Lawyer name</label>
                    <input id="hero-q" name="q" type="search" placeholder="Or search by lawyer / firm name" class="w-full border-0 bg-transparent py-2 text-sm placeholder:text-slate-400 focus:ring-0 focus:outline-none" maxlength="100">
                </div>
            </form>

            <dl class="mx-auto mt-10 grid max-w-3xl grid-cols-3 gap-4 text-center">
                <div><dt class="text-xs text-slate-500 sm:text-sm">Verified lawyers</dt><dd class="font-display text-2xl font-bold text-brand-900 sm:text-3xl">{{ number_format($stats['lawyers']) }}+</dd></div>
                <div><dt class="text-xs text-slate-500 sm:text-sm">Cities covered</dt><dd class="font-display text-2xl font-bold text-brand-900 sm:text-3xl">{{ number_format($stats['cities']) }}</dd></div>
                <div><dt class="text-xs text-slate-500 sm:text-sm">Practice areas</dt><dd class="font-display text-2xl font-bold text-brand-900 sm:text-3xl">{{ $stats['areas'] }}</dd></div>
            </dl>
        </div>
    </section>

    {{-- Practice areas --}}
    <section class="container-page py-14">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-brand-950">Browse by practice area</h2>
                <p class="mt-2 text-slate-600">Pick the kind of legal help you need.</p>
            </div>
            <a href="{{ route('lawyers.index') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-900 sm:inline-flex sm:items-center sm:gap-1">All lawyers <x-icon name="arrow-right" class="size-4"/></a>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($areas->take(12) as $a)
                <a href="{{ route('lawyers.index', ['area' => $a->slug]) }}" class="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm font-medium text-slate-800 transition hover:border-brand-300 hover:bg-brand-50">
                    <span class="flex items-center gap-3"><span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 group-hover:bg-white"><x-icon name="scale" class="size-4"/></span>{{ $a->name }}</span>
                    <x-icon name="chevron-right" class="size-4 shrink-0 text-slate-400 group-hover:text-brand-700"/>
                </a>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-slate-50 py-16">
        <div class="container-page">
            <h2 class="text-center text-3xl font-bold text-brand-950">How it works</h2>
            <p class="mx-auto mt-2 max-w-2xl text-center text-slate-600">Simple, private and free for clients to search.</p>
            <ol class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['search', 'Search & compare', 'Filter verified advocates by city, practice area, court, language and years of experience.'],
                    ['phone', 'Call or message us', 'Call our helpline, WhatsApp us, or request a callback with the lawyer\'s Profile ID.'],
                    ['users', 'Get connected', 'Our team confirms availability and connects you with the lawyer – no cold calls, no spam.'],
                ] as $i => [$icon, $t, $d])
                    <li class="card p-6">
                        <span class="inline-flex size-11 items-center justify-center rounded-xl bg-brand-900 text-gold-200"><x-icon :name="$icon" class="size-5"/></span>
                        <p class="mt-4 text-xs font-semibold tracking-widest text-gold-600 uppercase">Step {{ $i + 1 }}</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ $t }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $d }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="container-page py-16">
            <h2 class="text-3xl font-bold text-brand-950">Featured lawyers</h2>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $lawyer)<x-lawyer-card :lawyer="$lawyer"/>@endforeach
            </div>
        </section>
    @endif

    @if ($topCities->isNotEmpty())
        <section class="container-page py-6">
            <h2 class="text-2xl font-bold text-brand-950">Popular cities</h2>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($topCities as $c)
                    <a href="{{ route('lawyers.index', ['state' => $c->state_slug, 'city' => $c->slug]) }}" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-sm text-slate-700 hover:border-brand-300 hover:bg-brand-50"><x-icon name="map-pin" class="size-3.5 text-slate-400"/> Lawyers in {{ $c->name }}</a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA bands --}}
    <section class="container-page mt-12 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-brand-900 p-8 text-white sm:p-10">
            <h2 class="text-2xl font-bold sm:text-3xl">Need help choosing?</h2>
            <p class="mt-3 text-brand-100">Tell us your problem and our team will suggest suitable lawyers near you.</p>
            <x-contact-buttons class="mt-6" on-dark/>
        </div>
        <div class="rounded-2xl border border-gold-200 bg-gold-50 p-8 sm:p-10">
            <h2 class="text-2xl font-bold text-brand-950 sm:text-3xl">Are you an advocate?</h2>
            <p class="mt-3 text-slate-700">Listing is <strong>free</strong>. Create your verified profile and receive client enquiries through MAT LEGAL NETWORK.</p>
            <a href="{{ route('register') }}" class="btn-gold mt-6">Join the network – free <x-icon name="arrow-right" class="size-4"/></a>
        </div>
    </section>
</x-layouts.app>
