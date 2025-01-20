@php
    use function Stillat\Dagger\component;

    $theAlert = component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$theAlert->type]) }}>
    {{ $theAlert->message }}
</div>