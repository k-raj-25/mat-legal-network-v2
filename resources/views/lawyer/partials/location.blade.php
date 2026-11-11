<div class="grid gap-5 sm:grid-cols-2" x-data="cityPicker({ url: '{{ route('locations.cities', ':state') }}', state: @js(old('state_id', $lawyer?->state_id)), city: @js(old('city_id', $lawyer?->city_id)), key: 'id' })">
    <div>
        <label for="f_state_id" class="form-label">State <span class="text-rose-500" aria-hidden="true">*</span></label>
        <select id="f_state_id" name="state_id" x-model="state" required class="form-input" aria-invalid="{{ $errors->has('state_id') ? 'true' : 'false' }}">
            <option value="">Select state</option>
            @foreach ($states as $s)<option value="{{ $s->id }}" @selected((string) old('state_id', $lawyer?->state_id) === (string) $s->id)>{{ $s->name }}</option>@endforeach
        </select>
        @error('state_id')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="f_city_id" class="form-label">City <span class="text-rose-500" aria-hidden="true">*</span></label>
        <select id="f_city_id" name="city_id" x-model="city" required :disabled="!state || loading" class="form-input disabled:bg-slate-50" aria-invalid="{{ $errors->has('city_id') ? 'true' : 'false' }}">
            <option value="" x-text="loading ? 'Loading cities…' : (state ? 'Select city' : 'Select state first')">Select city</option>
            <template x-for="c in cities" :key="c.id"><option :value="String(c.id)" x-text="c.name" :selected="String(c.id) === city"></option></template>
        </select>
        <p class="form-help">City not listed? Choose the nearest one and tell us at {{ $site['company_email'] }}.</p>
        @error('city_id')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <x-input name="office_address" label="Office / chamber address" type="textarea" rows="2" :value="$lawyer?->office_address" maxlength="500" class="sm:col-span-2" help="Private – not shown on the website."/>
    <x-input name="pincode" label="PIN code" :value="$lawyer?->pincode" inputmode="numeric" maxlength="6" pattern="\d{6}"/>
    <x-input name="consultation_fee" label="Consultation fee (₹, optional)" type="number" min="0" max="1000000" step="1" :value="$lawyer?->consultation_fee" help="Indicative fee shown on your profile."/>
    <label class="flex items-center gap-2.5 text-sm text-slate-700 sm:col-span-2">
        <input type="hidden" name="offers_online_consultation" value="0">
        <input type="checkbox" name="offers_online_consultation" value="1" class="form-check" @checked(old('offers_online_consultation', $lawyer?->offers_online_consultation))>
        I offer online / video consultations
    </label>
    <x-input name="bio" label="About you" type="textarea" rows="5" :value="$lawyer?->bio" maxlength="2000" class="sm:col-span-2" placeholder="Describe your practice, the kind of matters you handle and how you work with clients." help="Do not include phone numbers, emails or claims of success (Bar Council rules)."/>
</div>
