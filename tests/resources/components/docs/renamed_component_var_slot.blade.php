@php
    use function Stillat\Dagger\component;

    $theAlert = component()->props(['type' => 'info']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$theAlert->type]) }}>
    {{ $slot }}
</div>