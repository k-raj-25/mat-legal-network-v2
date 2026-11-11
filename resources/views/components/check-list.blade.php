@props(['name', 'label', 'items', 'selected' => [], 'max' => 10, 'required' => false, 'searchable' => true, 'help' => null])
@php $selected = array_map('intval', (array) old($name, $selected)); @endphp
<fieldset x-data="checkList({{ $max }})" {{ $attributes->merge(['class' => '']) }} data-testid="checklist-{{ $name }}" @if ($required) data-required-group @endif>
    <div class="flex items-center justify-between gap-3">
        <legend class="form-label mb-0">{{ $label }} @if ($required)<span class="text-rose-500" aria-hidden="true">*</span>@endif</legend>
        <span class="text-xs text-slate-500"><span x-text="count"></span> / {{ $max }} selected</span>
    </div>
    @if ($help)<p class="form-help mb-2">{{ $help }}</p>@endif
    @if ($searchable && count($items) > 10)
        <input type="search" x-model="q" placeholder="Type to filter…" class="form-input mt-2 py-2" aria-label="Filter {{ strtolower($label) }}">
    @endif
    <div class="mt-2 grid max-h-64 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2 {{ $errors->has($name) ? 'border-rose-300' : '' }}">
        @foreach ($items as $item)
            <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50" x-show="matches(@js($item->name))">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $item->id }}" class="form-check" @checked(in_array($item->id, $selected, true))
                       @change="recount(); if (count > max) { $el.checked = false; recount(); }">
                {{ $item->name }}
            </label>
        @endforeach
    </div>
    @if ($required)<p class="form-error" data-group-error hidden>Please select at least one option.</p>@endif
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
    @error($name.'.*')<p class="form-error">{{ $message }}</p>@enderror
</fieldset>
