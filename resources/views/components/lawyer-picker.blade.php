@props(['name', 'label', 'lawyer' => null, 'help' => null, 'id' => null])
@php
    // Searchable combobox of approved lawyers. Submits the profile ID (e.g. MLN-000123)
    // under $name, exactly like the plain text input it replaces. Without JavaScript the
    // visible input itself carries the name, so the form still works by typing the ID.
    $id = $id ?? 'f_'.$name;
    $hasError = $errors->has($name);
    $oldCode = old($name);
    $code = $oldCode ?? $lawyer?->public_code ?? '';
    $selected = $lawyer && $code === $lawyer->public_code ? [
        'code' => $lawyer->public_code,
        'name' => $lawyer->full_name,
        'city' => $lawyer->city?->name,
        'areas' => $lawyer->practiceAreas->take(2)->pluck('name')->values(),
    ] : null;
    $text = $selected ? $selected['name'].' · '.$selected['code'] : $code;
@endphp
<div x-data="lawyerPicker(@js(['url' => route('admin.lawyers.lookup'), 'code' => $code, 'selected' => $selected, 'id' => $id, 'serverError' => $hasError]))"
     @focusout="onFocusOut($event)" @keydown.escape.prevent.stop="close(true)" class="relative" data-testid="lawyer-picker">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <input type="hidden" x-bind:name="'{{ $name }}'" x-model="code">
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" x-bind:name="null" type="text" value="{{ $text }}"
               x-model="query" @input="onInput()" @keydown="onKeydown($event)" @click="toggle()"
               role="combobox" aria-autocomplete="list" aria-haspopup="listbox"
               aria-controls="{{ $id }}_list" :aria-expanded="open ? 'true' : 'false'"
               :aria-activedescendant="active >= 0 ? '{{ $id }}_opt_' + active : null"
               aria-describedby="{{ $id }}_help {{ $id }}_error"
               :aria-invalid="invalid || serverError ? 'true' : 'false'"
               aria-invalid="{{ $hasError ? 'true' : 'false' }}"
               autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="80"
               placeholder="Search by name, ID or city"
               class="form-input pr-16 text-base sm:text-sm" x-ref="input">
        <div class="absolute inset-y-0 right-1.5 flex items-center gap-0.5">
            <button type="button" x-show="query" x-cloak @click="clear()" class="grid size-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Clear assigned lawyer">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
            <button type="button" x-cloak @click="toggle(); $refs.input.focus()" tabindex="-1" class="grid size-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Show lawyers">
                <svg class="size-4 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak class="absolute inset-x-0 z-30 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
        <ul id="{{ $id }}_list" role="listbox" aria-label="Approved lawyers" class="max-h-72 overflow-y-auto overscroll-contain py-1" x-ref="list">
            <template x-for="(r, i) in results" :key="r.code">
                <li :id="'{{ $id }}_opt_' + i" role="option" :aria-selected="r.code === code ? 'true' : 'false'"
                    @mousedown.prevent @click="choose(r)" @mousemove="active = i"
                    class="cursor-pointer px-3.5 py-2.5 text-sm" :class="i === active ? 'bg-brand-50' : ''">
                    <span class="flex items-baseline justify-between gap-3">
                        <span class="truncate font-medium text-ink" x-text="r.name"></span>
                        <span class="shrink-0 font-mono text-xs text-slate-500" x-text="r.code"></span>
                    </span>
                    <span class="block truncate text-xs text-slate-500" x-text="[r.city, r.areas.join(', ')].filter(Boolean).join(' · ')"></span>
                </li>
            </template>
        </ul>
        <p x-show="!loading && failed" class="px-3.5 py-3 text-sm text-rose-600">Could not load lawyers. Close and reopen the list to try again.</p>
        <p x-show="!loading && !failed && !results.length" class="px-3.5 py-3 text-sm text-slate-500">No approved lawyer matches “<span x-text="query"></span>”.</p>
        <p x-show="loading && !results.length" class="px-3.5 py-3 text-sm text-slate-500">Searching…</p>
        <p x-show="more" class="border-t border-slate-100 px-3.5 py-2 text-xs text-slate-500">Showing the first matches. Type more to narrow down.</p>
    </div>

    <p id="{{ $id }}_help" class="form-help">{{ $help }}</p>
    <div id="{{ $id }}_error">
        <p x-show="invalid" x-cloak class="form-error">Pick a lawyer from the list, or clear the field to leave it unassigned.</p>
        @error($name)<p x-show="serverError" class="form-error">{{ $message }}</p>@enderror
    </div>
    <p class="sr-only" role="status" aria-live="polite" x-text="announcement"></p>
</div>
