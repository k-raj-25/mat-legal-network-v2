@php
    $lawyer = null;
    $steps = ['Account', 'Personal', 'Professional', 'Location & fees', 'Documents'];
    $fieldStep = [
        'email' => 0, 'password' => 0,
        'full_name' => 1, 'gender' => 1, 'designation' => 1, 'phone' => 1, 'alternate_phone' => 1,
        'enrolment_number' => 2, 'bar_council' => 2, 'practice_since' => 2, 'education' => 2, 'firm_name' => 2, 'practice_areas' => 2, 'courts' => 2, 'languages' => 2,
        'state_id' => 3, 'city_id' => 3, 'office_address' => 3, 'pincode' => 3, 'consultation_fee' => 3, 'offers_online_consultation' => 3, 'bio' => 3,
    ];
    $start = 4;
    foreach ($errors->keys() as $k) { $start = min($start, $fieldStep[explode('.', $k)[0]] ?? 4); }
    if (! $errors->any()) { $start = 0; }
@endphp
<x-layouts.auth title="Join MAT LEGAL NETWORK" subtitle="Free listing for advocates. Your profile goes live after our team verifies your details." wide>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="card overflow-hidden" x-data="wizard(@js($steps), {{ $start }})" data-testid="register-form" @submit="$refs.submit.disabled = true">
        @csrf
        {{-- Progress --}}
        <ol class="flex border-b border-slate-200 bg-slate-50 text-xs font-medium" aria-label="Registration steps">
            <template x-for="(s, i) in steps" :key="i">
                <li class="flex-1">
                    <button type="button" @click="go(i)" class="flex w-full flex-col items-center gap-1.5 px-1 py-3 sm:flex-row sm:justify-center sm:gap-2"
                            :class="i === step ? 'text-brand-800' : (i < step ? 'text-emerald-700' : 'text-slate-400')" :aria-current="i === step ? 'step' : null">
                        <span class="inline-flex size-6 items-center justify-center rounded-full text-[11px] font-bold"
                              :class="i === step ? 'bg-brand-800 text-white' : (i < step ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500')" x-text="i < step ? '✓' : i + 1"></span>
                        <span class="hidden sm:inline" x-text="s"></span>
                    </button>
                </li>
            </template>
        </ol>

        <div class="p-6 sm:p-8">
            <x-flash/>
            @if ($errors->any())
                <p class="mb-6 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">For your security, uploaded files are not kept after an error – please attach your documents again in the last step.</p>
            @endif

            <section x-ref="step0" x-show="step === 0" data-step="0">
                <h2 class="text-xl font-semibold text-slate-900">Create your login</h2>
                <p class="mt-1 mb-6 text-sm text-slate-600">You'll use this to manage your profile.</p>
                <div class="grid gap-5">
                    <x-input name="email" type="email" label="Email" required autocomplete="email" maxlength="190"/>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-input name="password" type="password" label="Password" required minlength="8" autocomplete="new-password" help="8+ characters with letters and numbers."/>
                        <x-input name="password_confirmation" type="password" label="Confirm password" required minlength="8" autocomplete="new-password"/>
                    </div>
                </div>
            </section>

            <section x-ref="step1" x-show="step === 1" x-cloak data-step="1">
                <h2 class="text-xl font-semibold text-slate-900">Personal details</h2>
                <p class="mt-1 mb-6 text-sm text-slate-600">Your phone number is never shown to the public.</p>
                @include('lawyer.partials.personal')
            </section>

            <section x-ref="step2" x-show="step === 2" x-cloak data-step="2">
                <h2 class="text-xl font-semibold text-slate-900">Professional details</h2>
                <p class="mt-1 mb-6 text-sm text-slate-600">Clients filter by these – the more accurate, the better your matches.</p>
                @include('lawyer.partials.professional')
            </section>

            <section x-ref="step3" x-show="step === 3" x-cloak data-step="3">
                <h2 class="text-xl font-semibold text-slate-900">Location, fees &amp; about you</h2>
                <p class="mt-1 mb-6 text-sm text-slate-600">Where you practise and how you work.</p>
                @include('lawyer.partials.location')
            </section>

            <section x-ref="step4" x-show="step === 4" x-cloak data-step="4">
                <h2 class="text-xl font-semibold text-slate-900">Verification documents</h2>
                <p class="mt-1 mb-6 text-sm text-slate-600">We verify every advocate before their profile goes live.</p>
                @include('lawyer.partials.documents')
                <div class="mt-8 space-y-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" name="accept_terms" value="1" class="form-check mt-0.5" required @checked(old('accept_terms'))>
                        <span>I have read and agree to the <a href="{{ route('legal', 'terms') }}" target="_blank" class="font-medium text-brand-700 underline">Terms of Use</a>, <a href="{{ route('legal', 'lawyer-terms') }}" target="_blank" class="font-medium text-brand-700 underline">Lawyer Listing Terms</a> and <a href="{{ route('legal', 'privacy') }}" target="_blank" class="font-medium text-brand-700 underline">Privacy Policy</a>.</span>
                    </label>
                    @error('accept_terms')<p class="form-error">{{ $message }}</p>@enderror
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" name="declaration" value="1" class="form-check mt-0.5" required @checked(old('declaration'))>
                        <span>I declare that I am an advocate enrolled with a State Bar Council, that the information and documents I have provided are true, and that I will comply with the Bar Council of India Rules.</span>
                    </label>
                    @error('declaration')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:px-8">
            <button type="button" class="btn-outline" @click="prev()" x-show="step > 0" x-cloak>Back</button>
            <span x-show="step === 0" class="text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand-700">Log in</a></span>
            <button type="button" class="btn-primary" @click="next()" x-show="step < steps.length - 1" data-testid="wizard-next">Continue <x-icon name="arrow-right" class="size-4"/></button>
            <button type="submit" x-ref="submit" class="btn-gold" x-show="step === steps.length - 1" x-cloak data-testid="register-submit">Submit for verification</button>
        </div>
    </form>
</x-layouts.auth>
