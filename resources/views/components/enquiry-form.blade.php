@props(['lawyer' => null, 'areas', 'formToken'])
<div id="enquiry" class="scroll-mt-28">
    @if (session('enquiry_success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900" role="status" data-testid="enquiry-success">
            <div class="flex items-center gap-3">
                <x-icon name="check-circle" class="size-7 text-emerald-600"/>
                <h3 class="font-sans text-lg font-semibold">Request received</h3>
            </div>
            <p class="mt-2 text-sm leading-6">Thank you. Your reference number is <strong class="font-mono">{{ session('enquiry_success') }}</strong>. Our team will call you back shortly during {{ $site['support_hours'] }}. For anything urgent, call <a class="font-semibold underline" href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}">{{ $site['company_phone'] }}</a>.</p>
        </div>
    @else
        <form method="POST" action="{{ route('enquiries.store') }}" class="space-y-4" data-testid="enquiry-form" novalidate x-data="{ pref: '{{ old('preferred_contact', 'call') }}', busy: false }" @submit="busy = true">
            @csrf
            @if ($lawyer)<input type="hidden" name="lawyer" value="{{ $lawyer->public_code }}">@endif
            <input type="hidden" name="{{ \App\Support\SpamGuard::TIMER }}" value="{{ $formToken }}">
            {{-- Honeypot: humans never see or fill this --}}
            <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" name="{{ \App\Support\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></label></div>

            @if ($errors->any())
                <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">Please check the highlighted fields.</p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="name" label="Your name" required autocomplete="name" maxlength="100"/>
                <x-input name="phone" label="Mobile number" type="tel" required autocomplete="tel" inputmode="tel" maxlength="20" placeholder="10-digit mobile"/>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="email" label="Email (optional)" type="email" autocomplete="email" maxlength="190"/>
                <x-input name="city" label="Your city" autocomplete="address-level2" maxlength="100" :value="$lawyer?->city?->name"/>
            </div>
            @unless ($lawyer)
                <x-select name="practice_area_id" label="What do you need help with?" :options="$areas->pluck('name', 'id')" placeholder="Not sure / other"/>
            @endunless
            <fieldset>
                <legend class="form-label">How should we contact you?</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (['call' => 'Phone call', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $k => $l)
                        <label class="cursor-pointer">
                            <input type="radio" name="preferred_contact" value="{{ $k }}" x-model="pref" class="peer sr-only">
                            <span class="inline-block rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 peer-checked:border-brand-700 peer-checked:bg-brand-50 peer-checked:font-semibold peer-checked:text-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
                @error('preferred_contact')<p class="form-error">{{ $message }}</p>@enderror
            </fieldset>
            <x-input name="message" type="textarea" label="Briefly describe your matter" required minlength="10" maxlength="2000" rows="4" placeholder="E.g. I need help with a property dispute in Pune. The matter is at the notice stage." help="Please don't share confidential documents here. Our team will guide you on next steps."/>
            <label class="flex items-start gap-2.5 text-sm text-slate-600">
                <input type="checkbox" name="consent" value="1" class="form-check mt-0.5" @checked(old('consent')) required>
                <span>I agree to the <a href="{{ route('legal', 'privacy') }}" class="font-medium text-brand-700 underline" target="_blank">Privacy Policy</a>, <a href="{{ route('legal', 'terms') }}" class="font-medium text-brand-700 underline" target="_blank">Terms</a> and <a href="{{ route('legal', 'disclaimer') }}" class="font-medium text-brand-700 underline" target="_blank">Disclaimer</a>, and consent to MAT LEGAL NETWORK contacting me about this request{{ $lawyer ? ' and sharing it with the lawyer' : '' }}.</span>
            </label>
            @error('consent')<p class="form-error -mt-2">{{ $message }}</p>@enderror
            <button class="btn-gold w-full py-3" :disabled="busy" data-testid="enquiry-submit">
                <span x-show="!busy">Request a callback</span><span x-show="busy" x-cloak>Sending…</span>
            </button>
        </form>
    @endif
</div>
