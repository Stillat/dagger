@php
use function Stillat\Dagger\component;

component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>