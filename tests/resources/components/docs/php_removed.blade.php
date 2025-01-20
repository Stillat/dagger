@php
    use function Stillat\Dagger\component;

    $myCustomVar = 'the value';
    component()->props(['type' => 'info', 'message']);
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message ?? $myCustomVar ?? 'Nope' }}
</div>