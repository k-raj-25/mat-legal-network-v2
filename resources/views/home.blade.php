@php use App\Support\AreaStyle; @endphp
<x-layouts.app>
    {{-- ============ HERO ============ --}}
    <section class="relative -mt-[72px] overflow-hidden pt-[72px]">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="dot-grid absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_70%)]"></div>
            <div class="absolute -top-32 -left-32 size-[36rem] rounded-full bg-brand-300/40 blur-3xl"></div>
            <div class="absolute top-10 -right-40 size-[32rem] rounded-full bg-coral-300/40 blur-3xl"></div>
            <div class="absolute top-[28rem] left-1/3 size-[22rem] rounded-full bg-violet-300/25 blur-3xl"></div>
        </div>

        <div class="container-page grid items-center gap-14 pt-10 pb-20 lg:grid-cols-[1.1fr_0.9fr] lg:pt-16 lg:pb-28">
            <div class="animate-rise">
                <p class="eyebrow"><span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-brand-500"></span></span> {{ number_format($stats['lawyers']) }}+ verified advocates across India</p>
                <h1 class="mt-6 text-[2.75rem] leading-[1.02] sm:text-6xl lg:text-7xl">
                    Legal help,<br><span class="highlight">made simple.</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">Find the right lawyer by city, practice area, language and experience. One call to MAT connects you – <strong class="text-ink">your number is never shared publicly.</strong></p>

                <form action="{{ route('lawyers.index') }}" method="GET" class="mt-9 rounded-[28px] bg-white p-2.5 shadow-pop-lg ring-1 ring-slate-200/70"
                      x-data="cityPicker({ url: '{{ route('locations.cities', ':state') }}', key: 'slug' })" data-testid="hero-search"
                      @submit="[...$el.elements].forEach(e => { if (e.name && e.value === '') e.disabled = true })">
                    <div class="grid gap-2 sm:grid-cols-[1.3fr_1fr_1fr_auto]">
                        <label class="relative flex items-center rounded-2xl bg-slate-50 pl-3.5 transition focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-400">
                            <x-icon name="scale" class="size-5 shrink-0 text-brand-600"/>
                            <span class="sr-only">Practice area</span>
                            <select id="hero-area" name="area" class="w-full appearance-none border-0 bg-transparent py-3.5 pr-3 pl-2.5 text-sm font-semibold text-ink focus:ring-0 focus:outline-none">
                                <option value="">Any legal issue</option>
                                @foreach ($areas as $a)<option value="{{ $a->slug }}">{{ $a->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="relative flex items-center rounded-2xl bg-slate-50 pl-3.5 transition focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-400">
                            <x-icon name="map-pin" class="size-5 shrink-0 text-coral-500"/>
                            <span class="sr-only">State</span>
                            <select id="hero-state" name="state" x-model="state" class="w-full appearance-none border-0 bg-transparent py-3.5 pr-3 pl-2.5 text-sm font-semibold text-ink focus:ring-0 focus:outline-none">
                                <option value="">Any state</option>
                                @foreach ($states as $s)<option value="{{ $s->slug }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="relative flex items-center rounded-2xl bg-slate-50 pl-3.5 transition focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-400" :class="!state && 'opacity-60'">
                            <x-icon name="building" class="size-5 shrink-0 text-violet-500"/>
                            <span class="sr-only">City</span>
                            <select id="hero-city" name="city" x-model="city" :disabled="!state || loading" class="w-full appearance-none border-0 bg-transparent py-3.5 pr-3 pl-2.5 text-sm font-semibold text-ink focus:ring-0 focus:outline-none">
                                <option value="" x-text="loading ? 'Loading…' : (state ? 'Any city' : 'City')">City</option>
                                <template x-for="c in cities" :key="c.id"><option :value="c.slug" x-text="c.name"></option></template>
                            </select>
                        </label>
                        <button class="btn-primary rounded-2xl px-6 py-3.5 text-base"><x-icon name="search" class="size-5"/> Search</button>
                    </div>
                    <label class="mt-1 flex items-center gap-2.5 rounded-2xl px-3.5 py-1.5">
                        <x-icon name="user" class="size-4 text-slate-400"/>
                        <span class="sr-only">Lawyer name</span>
                        <input id="hero-q" name="q" type="search" placeholder="…or search a lawyer / firm by name" class="w-full border-0 bg-transparent py-1.5 text-sm placeholder:text-slate-400 focus:ring-0 focus:outline-none" maxlength="100">
                    </label>
                </form>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-sm font-semibold text-slate-500">Popular:</span>
                    @foreach ($areas->whereIn('slug', ['criminal-law', 'family-divorce', 'property-real-estate', 'cheque-bounce-ni-act'])->values() as $a)
                        @php [$icon, $tone] = AreaStyle::for($a->slug); @endphp
                        <a href="{{ route('lawyers.index', ['area' => $a->slug]) }}" class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-bold transition hover:-translate-y-0.5 {{ $tone }}"><x-icon :name="$icon" class="size-4"/>{{ Str::before($a->name, ' (') }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Illustration: floating product cards --}}
            <div class="relative mx-auto hidden h-[500px] w-full max-w-md sm:block" aria-hidden="true">
                <div class="absolute inset-6 rotate-6 rounded-[44px] bg-gradient-to-br from-brand-400 to-brand-600"></div>
                <div class="absolute inset-6 -rotate-3 rounded-[44px] bg-gradient-to-br from-coral-300 to-coral-500 opacity-90"></div>
                <div class="absolute inset-x-10 top-16 animate-float rounded-[28px] bg-white p-5 shadow-pop-lg">
                    <div class="flex items-center gap-3.5">
                        <span class="tile size-14 bg-gradient-to-br from-violet-500 to-indigo-600 text-lg font-extrabold text-white">PS</span>
                        <div>
                            <p class="font-display text-lg font-extrabold text-ink">Adv. Priya S.</p>
                            <p class="text-sm text-slate-500">Family Law · Mumbai</p>
                        </div>
                        <span class="ml-auto tile size-8 bg-brand-100 text-brand-700"><x-icon name="check" class="size-4"/></span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="chip bg-pink-100 text-pink-700">Divorce</span><span class="chip bg-amber-100 text-amber-700">Property</span><span class="chip">12 yrs exp.</span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <span class="btn-primary py-2.5 text-xs"><x-icon name="phone" class="size-4"/> Call MAT</span>
                        <span class="btn-whatsapp py-2.5 text-xs"><x-icon name="whatsapp" class="size-4"/> WhatsApp</span>
                    </div>
                </div>
                <div class="absolute bottom-16 -left-4 animate-float rounded-2xl bg-ink p-4 text-white shadow-pop-lg [animation-delay:-2s]">
                    <div class="flex items-center gap-3">
                        <span class="tile size-10 bg-coral-500"><x-icon name="chat" class="size-5"/></span>
                        <div><p class="text-sm font-bold">Callback requested</p><p class="text-xs text-slate-400">Our team will call you back</p></div>
                    </div>
                </div>
                <div class="absolute right-0 bottom-36 animate-float rounded-2xl bg-white px-4 py-3 shadow-pop-lg [animation-delay:-4s]">
                    <p class="flex items-center gap-2 text-sm font-bold text-ink"><x-icon name="lock" class="size-4 text-brand-600"/> Number kept private</p>
                </div>
                <div class="absolute top-4 right-6 tile size-14 rotate-12 bg-amber-300 text-ink shadow-pop"><x-icon name="star" class="size-7"/></div>
            </div>
        </div>
    </section>

    {{-- ============ STATS STRIP ============ --}}
    <section class="container-page">
        <dl class="grid grid-cols-3 divide-x divide-slate-200 rounded-[28px] border border-slate-200/80 bg-white py-6 shadow-pop">
            @foreach ([[number_format($stats['lawyers']).'+', 'Verified lawyers', 'text-brand-600'], [$stats['cities'], 'Cities covered', 'text-coral-600'], [$stats['areas'], 'Practice areas', 'text-violet-600']] as [$v, $l, $c])
                <div class="px-2 text-center"><dd class="font-display text-3xl font-extrabold sm:text-4xl {{ $c }}">{{ $v }}</dd><dt class="mt-1 text-xs font-semibold text-slate-500 sm:text-sm">{{ $l }}</dt></div>
            @endforeach
        </dl>
    </section>

    {{-- ============ PRACTICE AREAS ============ --}}
    <section class="container-page py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-extrabold tracking-wider text-coral-600 uppercase">Browse by need</p>
                <h2 class="mt-2 text-4xl sm:text-5xl">What do you need help with?</h2>
            </div>
            <a href="{{ route('lawyers.index') }}" class="btn-outline">See all lawyers <x-icon name="arrow-right" class="size-4"/></a>
        </div>
        <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($areas->take(12) as $a)
                @php [$icon, $tone] = AreaStyle::for($a->slug); @endphp
                <a href="{{ route('lawyers.index', ['area' => $a->slug]) }}" class="group relative flex flex-col gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 transition duration-200 hover:-translate-y-1 hover:border-transparent hover:shadow-pop-lg">
                    <span class="tile size-12 {{ $tone }} transition group-hover:scale-110 group-hover:-rotate-6"><x-icon :name="$icon" class="size-6"/></span>
                    <span class="font-display text-base leading-snug font-extrabold text-ink sm:text-lg">{{ $a->name }}</span>
                    <x-icon name="arrow-right" class="absolute top-5 right-5 size-5 -translate-x-1 text-slate-300 opacity-0 transition group-hover:translate-x-0 group-hover:text-ink group-hover:opacity-100"/>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="relative overflow-hidden bg-ink py-20 text-white">
        <div class="pointer-events-none absolute -top-40 right-0 size-[30rem] rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 left-0 size-[26rem] rounded-full bg-coral-500/25 blur-3xl"></div>
        <div class="container-page relative">
            <p class="text-sm font-extrabold tracking-wider text-brand-300 uppercase">How it works</p>
            <h2 class="mt-2 max-w-2xl text-4xl text-white sm:text-5xl">Three steps. Zero spam calls.</h2>
            <ol class="mt-12 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['search', 'Search & compare', 'Filter verified advocates by city, practice area, court, language and experience.', 'bg-brand-400 text-ink', 'step-search'],
                    ['phone', 'Call or message MAT', 'Call our helpline, WhatsApp us, or request a callback quoting the lawyer\'s Profile ID.', 'bg-coral-400 text-ink', 'step-call'],
                    ['users', 'Get connected', 'We confirm availability and connect you with the lawyer – your number stays private.', 'bg-amber-300 text-ink', 'step-connect'],
                ] as $i => [$icon, $t, $d, $tone, $art])
                    <li class="rounded-3xl bg-white/[0.06] p-7 ring-1 ring-white/10 backdrop-blur">
                        <x-art :name="$art" class="mb-6 h-auto w-full rounded-2xl"/>
                        <div class="flex items-center justify-between">
                            <span class="tile size-14 {{ $tone }}"><x-icon :name="$icon" class="size-7"/></span>
                            <span class="font-display text-6xl font-extrabold text-white/10">0{{ $i + 1 }}</span>
                        </div>
                        <h3 class="mt-6 text-2xl text-white">{{ $t }}</h3>
                        <p class="mt-2 leading-7 text-slate-300">{{ $d }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="container-page py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold tracking-wider text-brand-600 uppercase">Hand-picked</p>
                    <h2 class="mt-2 text-4xl sm:text-5xl">Featured lawyers</h2>
                </div>
                <a href="{{ route('lawyers.index') }}" class="btn-outline">Browse all <x-icon name="arrow-right" class="size-4"/></a>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $lawyer)<x-lawyer-card :lawyer="$lawyer"/>@endforeach
            </div>
        </section>
    @endif

    @if ($topCities->isNotEmpty())
        <section class="container-page {{ $featured->isEmpty() ? 'pt-20' : '' }}">
            <x-art name="city-skyline" class="mb-8 h-auto w-full"/>
            <h2 class="text-3xl">Popular cities</h2>
            <div class="mt-6 flex flex-wrap gap-2.5">
                @foreach ($topCities as $c)
                    <a href="{{ route('lawyers.index', ['state' => $c->state_slug, 'city' => $c->slug]) }}" class="group inline-flex items-center gap-2 rounded-full border-2 border-slate-200 bg-white py-2 pr-4 pl-2 text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:border-ink">
                        <span class="tile size-7 rounded-full bg-coral-100 text-coral-600 group-hover:bg-coral-500 group-hover:text-white"><x-icon name="map-pin" class="size-4"/></span>{{ $c->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ CTA ============ --}}
    <section class="container-page mt-20 grid gap-5 lg:grid-cols-2">
        <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-brand-500 to-brand-700 p-8 text-white sm:p-12">
            <x-art name="cta-help" class="pointer-events-none absolute top-6 right-6 hidden size-40 sm:block"/>
            <span class="tile size-14 bg-white/15"><x-icon name="chat" class="size-7"/></span>
            <h2 class="relative mt-6 text-3xl text-white sm:text-4xl">Not sure who to pick?</h2>
            <p class="relative mt-3 max-w-md text-lg text-brand-50">Tell us your problem. Our team will suggest verified lawyers near you – free.</p>
            <x-contact-buttons class="relative mt-8" on-dark/>
        </div>
        <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-coral-400 to-coral-600 p-8 text-white sm:p-12">
            <x-art name="cta-advocate" class="pointer-events-none absolute top-6 right-6 hidden size-40 sm:block"/>
            <span class="tile size-14 bg-white/15"><x-icon name="briefcase" class="size-7"/></span>
            <h2 class="relative mt-6 text-3xl text-white sm:text-4xl">Are you an advocate?</h2>
            <p class="relative mt-3 max-w-md text-lg text-coral-50">Listing is <strong>100% free</strong>. Get a verified profile and receive client enquiries through MAT.</p>
            <a href="{{ route('register') }}" class="btn relative mt-8 bg-white px-6 py-3 text-base text-coral-700 hover:-translate-y-0.5 hover:bg-coral-50">Join the network <x-icon name="arrow-right" class="size-5"/></a>
        </div>
    </section>
</x-layouts.app>
