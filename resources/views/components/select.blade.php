@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => 'Select…', 'required' => false, 'id' => null, 'help' => null])
@php
    $id = $id ?? 'f_'.$name;
    $current = (string) old($name, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500" aria-hidden="true">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required) aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
        @if ($placeholder !== false)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
        @foreach ($options as $k => $v)
            <option value="{{ $k }}" @selected($current === (string) $k)>{{ $v }}</option>
        @endforeach
    </select>
    @if ($help)<p class="form-help">{{ $help }}</p>@endif
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
