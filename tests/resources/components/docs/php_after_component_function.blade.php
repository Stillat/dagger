@php
    use function Stillat\Dagger\component;

    component()->props(['type' => 'info', 'message']);

    $myCustomVar = 'the value';
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message ?? $myCustomVar ?? 'Nope' }}
</div>