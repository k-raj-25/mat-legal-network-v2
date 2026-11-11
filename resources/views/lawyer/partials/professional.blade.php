<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="enrolment_number" label="Bar enrolment number" required :value="$lawyer?->enrolment_number" maxlength="60" placeholder="e.g. D/1234/2015" help="Private – used for verification only."/>
    <div>
        <label for="f_bar_council" class="form-label">State Bar Council <span class="text-rose-500" aria-hidden="true">*</span></label>
        <input id="f_bar_council" name="bar_council" list="bar-councils" value="{{ old('bar_council', $lawyer?->bar_council) }}" required maxlength="150" class="form-input" aria-invalid="{{ $errors->has('bar_council') ? 'true' : 'false' }}" placeholder="Start typing…">
        <datalist id="bar-councils">@foreach ($barCouncils as $b)<option value="{{ $b }}">@endforeach</datalist>
        @error('bar_council')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <x-select name="practice_since" label="Practising since (year)" required :options="collect(range(now()->year, 1950))->mapWithKeys(fn ($y) => [$y => $y])" :value="$lawyer?->practice_since" placeholder="Select year"/>
    <x-input name="education" label="Education" :value="$lawyer?->education" maxlength="200" placeholder="e.g. B.A. LL.B. (Hons.), LL.M."/>
    <x-input name="firm_name" label="Firm / chamber name (optional)" :value="$lawyer?->firm_name" maxlength="150" class="sm:col-span-2"/>
    <x-check-list name="practice_areas" label="Practice areas" :items="$areas" :selected="$lawyer?->practiceAreas->pluck('id')->all() ?? []" required help="Choose up to 10 areas you actively handle." class="sm:col-span-2"/>
    <x-check-list name="courts" label="Courts you practise in" :items="$courts" :selected="$lawyer?->courts->pluck('id')->all() ?? []" class="sm:col-span-2"/>
    <x-check-list name="languages" label="Languages you can consult in" :items="$languages" :selected="$lawyer?->languages->pluck('id')->all() ?? []" required class="sm:col-span-2"/>
</div>
