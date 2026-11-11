<x-layouts.app :title="$lawyer->full_name.' – '.($lawyer->practiceAreas->first()?->name ?? 'Lawyer').' in '.$lawyer->city?->name"
               :description="$lawyer->full_name.', '.($lawyer->designation ?? 'Advocate').' in '.$lawyer->locationLabel().' with '.$lawyer->experienceLabel().' experience. Practice areas: '.$lawyer->practiceAreas->pluck('name')->implode(', ').'. Connect via MAT LEGAL NETWORK.'">
    <div class="border-b border-slate-200 bg-gradient-to-b from-brand-50 to-white">
        <div class="container-page py-8">
            <nav class="text-xs text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-slate-700">Home</a> <span class="mx-1">/</span>
                <a href="{{ route('lawyers.index') }}" class="hover:text-slate-700">Lawyers</a> <span class="mx-1">/</span>
                <a href="{{ route('lawyers.index', ['state' => $lawyer->state->slug, 'city' => $lawyer->city->slug]) }}" class="hover:text-slate-700">{{ $lawyer->city->name }}</a> <span class="mx-1">/</span>
                <span class="text-slate-700">{{ $lawyer->full_name }}</span>
            </nav>
            <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-center">
                <x-avatar :lawyer="$lawyer" size="size-28" text="text-3xl"/>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge bg-emerald-50 text-emerald-800 ring-emerald-200"><x-icon name="shield" class="size-3.5"/> Verified by MAT</span>
                        @if ($lawyer->is_featured)<span class="badge bg-gold-50 text-gold-700 ring-gold-200"><x-icon name="star" class="size-3.5"/> Featured</span>@endif
                    </div>
                    <h1 class="mt-2 text-3xl font-bold text-brand-950 sm:text-4xl" data-testid="lawyer-name">{{ $lawyer->full_name }}</h1>
                    <p class="mt-1 text-slate-600">{{ $lawyer->designation ?? 'Advocate' }}@if ($lawyer->firm_name) · {{ $lawyer->firm_name }}@endif</p>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="size-4 text-slate-400"/> {{ $lawyer->locationLabel() }}</span>
                        <span class="inline-flex items-center gap-1.5"><x-icon name="briefcase" class="size-4 text-slate-400"/> {{ $lawyer->experienceLabel() }} experience (since {{ $lawyer->practice_since }})</span>
                        @if ($lawyer->offers_online_consultation)<span class="inline-flex items-center gap-1.5 text-emerald-700"><x-icon name="video" class="size-4"/> Online consultation</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-page grid grid-cols-1 gap-8 py-10 lg:grid-cols-[1fr_380px]">
        <div class="min-w-0 space-y-8">
            @if ($lawyer->bio)
                <section class="card p-6">
                    <h2 class="text-xl font-semibold text-slate-900">About</h2>
                    <div class="mt-3 leading-7 whitespace-pre-line text-slate-700">{{ $lawyer->bio }}</div>
                </section>
            @endif

            <section class="card p-6">
                <h2 class="text-xl font-semibold text-slate-900">Practice areas</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($lawyer->practiceAreas as $a)
                        <a href="{{ route('lawyers.index', ['area' => $a->slug, 'state' => $lawyer->state->slug, 'city' => $lawyer->city->slug]) }}" class="chip px-3 py-1 text-sm hover:bg-brand-100">{{ $a->name }}</a>
                    @endforeach
                </div>
            </section>

            <section class="card divide-y divide-slate-100">
                <h2 class="px-6 pt-6 pb-4 text-xl font-semibold text-slate-900">Professional details</h2>
                <dl class="grid gap-x-6 sm:grid-cols-2">
                    @foreach (array_filter([
                        ['briefcase', 'Experience', $lawyer->experienceLabel()],
                        ['building', 'Courts', $lawyer->courts->pluck('name')->implode(', ') ?: null],
                        ['language', 'Languages', $lawyer->languages->pluck('name')->implode(', ') ?: null],
                        ['academic', 'Education', $lawyer->education],
                        ['clock', 'Consultation fee', $lawyer->consultation_fee !== null ? '₹'.number_format($lawyer->consultation_fee).' (indicative)' : 'Ask our team'],
                        ['user', 'Profile ID', $lawyer->public_code],
                    ], fn ($r) => $r[2]) as [$icon, $label, $value])
                        <div class="flex gap-3 border-t border-slate-100 px-6 py-4">
                            <x-icon :name="$icon" class="mt-0.5 size-5 shrink-0 text-brand-600"/>
                            <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $label }}</dt><dd class="mt-0.5 text-sm text-slate-800" @if ($label === 'Profile ID') data-testid="profile-id" @endif>{{ $value }}</dd></div>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card p-6" aria-labelledby="enq-title">
                <h2 id="enq-title" class="text-xl font-semibold text-slate-900">Request a callback about {{ $lawyer->full_name }}</h2>
                <p class="mt-1 mb-5 text-sm text-slate-600">Share a few details and our team will call you to arrange a consultation.</p>
                <x-enquiry-form :lawyer="$lawyer" :areas="$areas" :form-token="$formToken"/>
            </section>
        </div>

        <aside class="space-y-6">
            <div class="card p-6 lg:sticky lg:top-24" data-testid="contact-panel">
                <h2 class="font-sans text-lg font-semibold text-slate-900">Consult this lawyer</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">All consultations are arranged through MAT LEGAL NETWORK. Quote Profile ID <strong class="font-mono text-slate-900">{{ $lawyer->public_code }}</strong> when you call.</p>
                <x-contact-buttons :lawyer="$lawyer" stacked class="mt-5"/>
                <a href="#enquiry" class="btn-outline mt-2.5 w-full py-3"><x-icon name="chat" class="size-4"/> Request a callback</a>
                <ul class="mt-5 space-y-2 border-t border-slate-100 pt-5 text-sm text-slate-600">
                    <li class="flex gap-2"><x-icon name="lock" class="size-4 shrink-0 text-brand-600"/> Your number is shared only with this lawyer, with your consent.</li>
                    <li class="flex gap-2"><x-icon name="clock" class="size-4 shrink-0 text-brand-600"/> {{ $site['support_hours'] }}</li>
                </ul>
            </div>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="container-page pb-6">
            <h2 class="text-2xl font-bold text-brand-950">More lawyers in {{ $lawyer->city->name }}</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($similar as $s)<x-lawyer-card :lawyer="$s"/>@endforeach
            </div>
        </section>
    @endif

    {{-- Mobile sticky contact bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-2 gap-2 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:hidden">
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="btn-primary py-3"><x-icon name="phone" class="size-4"/> Call</a>
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $site['company_whatsapp']) }}?text={{ rawurlencode('Hello MAT LEGAL NETWORK, I would like to consult '.$lawyer->full_name.' (Profile ID: '.$lawyer->public_code.').') }}" target="_blank" rel="noopener" class="btn-whatsapp py-3"><x-icon name="whatsapp" class="size-4"/> WhatsApp</a>
    </div>
    <div class="h-16 lg:hidden"></div>
</x-layouts.app>
