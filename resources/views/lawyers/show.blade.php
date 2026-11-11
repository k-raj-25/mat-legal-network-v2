@php use App\Support\AreaStyle; @endphp
<x-layouts.app :title="$lawyer->full_name.' – '.($lawyer->practiceAreas->first()?->name ?? 'Lawyer').' in '.$lawyer->city?->name"
               :description="$lawyer->full_name.', '.($lawyer->designation ?? 'Advocate').' in '.$lawyer->locationLabel().' with '.$lawyer->experienceLabel().' experience. Practice areas: '.$lawyer->practiceAreas->pluck('name')->implode(', ').'. Connect via MAT LEGAL NETWORK.'">
    <div class="container-page pt-4">
        <nav class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">Home</a> <x-icon name="chevron-right" class="size-3"/>
            <a href="{{ route('lawyers.index') }}" class="hover:text-ink">Lawyers</a> <x-icon name="chevron-right" class="size-3"/>
            <a href="{{ route('lawyers.index', ['state' => $lawyer->state->slug, 'city' => $lawyer->city->slug]) }}" class="hover:text-ink">{{ $lawyer->city->name }}</a> <x-icon name="chevron-right" class="size-3"/>
            <span class="text-ink">{{ $lawyer->full_name }}</span>
        </nav>

        {{-- Cover + identity --}}
        <section class="mt-4 overflow-hidden rounded-[32px] border border-slate-200/80 bg-white shadow-pop">
            <div class="relative h-32 bg-gradient-to-r from-brand-500 via-brand-400 to-coral-400 sm:h-40">
                <div class="dot-grid absolute inset-0 opacity-40 mix-blend-overlay"></div>
                <div class="absolute -right-10 -bottom-20 size-56 rounded-full bg-white/15"></div>
                <div class="absolute top-6 right-40 size-16 rounded-full bg-amber-300/70 blur-sm"></div>
            </div>
            <div class="px-6 pb-7 sm:px-8">
                <div class="relative -mt-14 flex flex-col gap-4 sm:-mt-16 sm:flex-row sm:items-start sm:gap-5">
                    <x-avatar :lawyer="$lawyer" size="size-28 sm:size-32" text="text-3xl"/>
                    <div class="min-w-0 flex-1 sm:pt-[4.75rem]">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge bg-brand-50 text-brand-700 ring-brand-200"><x-icon name="shield" class="size-3.5"/> Verified by MAT</span>
                            @if ($lawyer->is_featured)<span class="badge bg-amber-50 text-amber-700 ring-amber-200"><x-icon name="star" class="size-3.5"/> Featured</span>@endif
                            @if ($lawyer->offers_online_consultation)<span class="badge bg-violet-50 text-violet-700 ring-violet-200"><x-icon name="video" class="size-3.5"/> Online consultation</span>@endif
                        </div>
                        <h1 class="mt-2 text-3xl sm:text-4xl" data-testid="lawyer-name">{{ $lawyer->full_name }}</h1>
                        <p class="mt-1 font-semibold text-slate-500">{{ $lawyer->designation ?? 'Advocate' }}@if ($lawyer->firm_name) · {{ $lawyer->firm_name }}@endif</p>
                    </div>
                </div>
                <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    @foreach ([
                        ['briefcase', 'Experience', $lawyer->experienceLabel(), 'bg-brand-100 text-brand-700'],
                        ['map-pin', 'Location', $lawyer->locationLabel(), 'bg-coral-100 text-coral-700'],
                        ['language', 'Languages', $lawyer->languages->pluck('name')->take(3)->implode(', ') ?: '—', 'bg-violet-100 text-violet-700'],
                        ['cash', 'Consultation', $lawyer->consultation_fee !== null ? '₹'.number_format($lawyer->consultation_fee) : 'Ask our team', 'bg-amber-100 text-amber-700'],
                    ] as [$icon, $label, $value, $tone])
                        <div class="flex min-w-0 items-center gap-3 rounded-2xl bg-slate-50 p-3 max-sm:flex-col max-sm:items-start">
                            <span class="tile size-10 {{ $tone }}"><x-icon :name="$icon" class="size-5"/></span>
                            <div class="min-w-0"><dt class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">{{ $label }}</dt><dd class="text-sm leading-snug font-bold break-words text-ink lg:truncate">{{ $value }}</dd></div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    </div>

    <div class="container-page grid grid-cols-1 gap-6 py-6 lg:grid-cols-[1fr_380px]">
        <div class="min-w-0 space-y-6">
            @if ($lawyer->bio)
                <section class="card p-6 sm:p-8">
                    <h2 class="text-2xl">About</h2>
                    <div class="mt-3 leading-7 whitespace-pre-line text-slate-600">{{ $lawyer->bio }}</div>
                </section>
            @endif

            <section class="card p-6 sm:p-8">
                <h2 class="text-2xl">Practice areas</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($lawyer->practiceAreas as $a)
                        @php [$icon, $tone] = AreaStyle::for($a->slug); @endphp
                        <a href="{{ route('lawyers.index', ['area' => $a->slug, 'state' => $lawyer->state->slug, 'city' => $lawyer->city->slug]) }}" class="group flex items-center gap-3 rounded-2xl border-2 border-slate-100 p-3 font-bold text-ink transition hover:border-ink">
                            <span class="tile size-10 {{ $tone }}"><x-icon :name="$icon" class="size-5"/></span>{{ $a->name }}
                            <x-icon name="arrow-right" class="ml-auto size-4 text-slate-300 transition group-hover:text-ink"/>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="card p-6 sm:p-8">
                <h2 class="text-2xl">Professional details</h2>
                <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    @foreach (array_filter([
                        ['Practising since', $lawyer->practice_since.' ('.$lawyer->experienceLabel().')'],
                        ['Courts', $lawyer->courts->pluck('name')->implode(', ') ?: null],
                        ['Languages', $lawyer->languages->pluck('name')->implode(', ') ?: null],
                        ['Education', $lawyer->education],
                        ['Consultation fee', $lawyer->consultation_fee !== null ? '₹'.number_format($lawyer->consultation_fee).' (indicative)' : 'Ask our team'],
                        ['Profile ID', $lawyer->public_code],
                    ], fn ($r) => $r[1]) as [$label, $value])
                        <div class="border-l-4 {{ $label === 'Profile ID' ? 'border-coral-400' : 'border-brand-200' }} pl-4">
                            <dt class="text-xs font-bold tracking-wide text-slate-400 uppercase">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold text-ink" @if ($label === 'Profile ID') data-testid="profile-id" @endif>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card p-6 sm:p-8" aria-labelledby="enq-title">
                <span class="tile size-12 bg-coral-100 text-coral-600"><x-icon name="chat" class="size-6"/></span>
                <h2 id="enq-title" class="mt-4 text-2xl">Request a callback</h2>
                <p class="mt-1 mb-6 text-slate-500">Share a few details about your matter and our team will call you to arrange a consultation with {{ $lawyer->full_name }}.</p>
                <x-enquiry-form :lawyer="$lawyer" :areas="$areas" :form-token="$formToken"/>
            </section>
        </div>

        <aside class="space-y-6">
            <div class="relative overflow-hidden rounded-[28px] bg-ink p-6 text-white lg:sticky lg:top-24" data-testid="contact-panel">
                <div class="pointer-events-none absolute -top-16 -right-16 size-48 rounded-full bg-brand-500/30 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-16 -left-10 size-40 rounded-full bg-coral-500/25 blur-2xl"></div>
                <div class="relative">
                    <h2 class="text-xl text-white">Consult this lawyer</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">All consultations are arranged through MAT LEGAL NETWORK. Quote this Profile ID when you call:</p>
                    <p class="mt-3 inline-flex rounded-xl bg-white/10 px-3 py-2 font-mono text-lg font-bold tracking-wider text-coral-300 ring-1 ring-white/15">{{ $lawyer->public_code }}</p>
                    <x-contact-buttons :lawyer="$lawyer" stacked on-dark class="mt-5"/>
                    <a href="#enquiry" class="btn mt-2.5 w-full bg-white/10 py-3 text-white ring-1 ring-white/15 hover:bg-white/15"><x-icon name="chat" class="size-4"/> Request a callback</a>
                    <ul class="mt-6 space-y-2.5 border-t border-white/10 pt-5 text-sm text-slate-300">
                        <li class="flex gap-2.5"><x-icon name="lock" class="size-5 shrink-0 text-brand-300"/> Your number is shared only with this lawyer, with your consent.</li>
                        <li class="flex gap-2.5"><x-icon name="clock" class="size-5 shrink-0 text-brand-300"/> {{ $site['support_hours'] }}</li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="container-page pt-10">
            <h2 class="text-3xl">More lawyers in {{ $lawyer->city->name }}</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($similar as $s)<x-lawyer-card :lawyer="$s"/>@endforeach
            </div>
        </section>
    @endif

    {{-- Mobile sticky contact bar --}}
    <div class="fixed inset-x-3 bottom-3 z-30 grid grid-cols-2 gap-2 rounded-full bg-ink/95 p-1.5 shadow-pop-lg backdrop-blur lg:hidden">
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="btn bg-white py-3 text-ink"><x-icon name="phone" class="size-4"/> Call</a>
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $site['company_whatsapp']) }}?text={{ rawurlencode('Hello MAT LEGAL NETWORK, I would like to consult '.$lawyer->full_name.' (Profile ID: '.$lawyer->public_code.').') }}" target="_blank" rel="noopener" class="btn-whatsapp py-3"><x-icon name="whatsapp" class="size-4"/> WhatsApp</a>
    </div>
    <div class="h-20 lg:hidden"></div>
</x-layouts.app>
