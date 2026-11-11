@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'id' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500" aria-hidden="true">*</span>@endif</label>
    @endif
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" @required($required) aria-invalid="{{ $hasError ? 'true' : 'false' }}" @if ($help) aria-describedby="{{ $id }}_help" @endif {{ $attributes->except('class')->merge(['class' => 'form-input', 'rows' => 4]) }}>{{ old($key, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password' && $type !== 'file') value="{{ old($key, $value) }}" @endif @required($required) aria-invalid="{{ $hasError ? 'true' : 'false' }}" @if ($help) aria-describedby="{{ $id }}_help" @endif {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
    @endif
    @if ($help)<p id="{{ $id }}_help" class="form-help">{{ $help }}</p>@endif
    @error($key)<p class="form-error">{{ $message }}</p>@enderror
</div>
