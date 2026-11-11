@if (session('status'))
    <div role="status" class="mb-6 flex items-start gap-3 rounded-2xl bg-brand-50 px-4 py-3.5 text-sm font-semibold text-brand-800 ring-1 ring-brand-200">
        <x-icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-brand-600"/>
        <span>{{ session('status') }}</span>
    </div>
@endif
@if ($errors->any() && ! ($hideErrors ?? false))
    <div role="alert" class="mb-6 rounded-2xl bg-rose-50 px-4 py-3.5 text-sm text-rose-900 ring-1 ring-rose-200">
        <p class="font-semibold">Please fix the highlighted {{ Str::plural('field', $errors->count()) }}:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
