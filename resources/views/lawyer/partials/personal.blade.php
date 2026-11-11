<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="full_name" label="Full name (as on Bar enrolment)" required :value="$lawyer?->full_name" maxlength="120" autocomplete="name" placeholder="e.g. Adv. Priya Sharma" class="sm:col-span-2"/>
    <x-select name="gender" label="Gender" :options="config('mat.genders')" :value="$lawyer?->gender" placeholder="Prefer not to say"/>
    <x-select name="designation" label="Designation" :options="array_combine(config('mat.designations'), config('mat.designations'))" :value="$lawyer?->designation ?? 'Advocate'" placeholder="Select…"/>
    <x-input name="phone" label="Mobile number" type="tel" required :value="$lawyer?->phone" inputmode="tel" maxlength="20" autocomplete="tel" help="Private – used only by our team to send you client requests. Never shown publicly."/>
    <x-input name="alternate_phone" label="Alternate number (optional)" type="tel" :value="$lawyer?->alternate_phone" inputmode="tel" maxlength="20"/>
</div>
