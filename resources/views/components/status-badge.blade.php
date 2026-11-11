@props(['status'])
<span {{ $attributes->merge(['class' => 'badge '.$status->badgeClasses()]) }}>{{ $status->label() }}</span>
