<!-- /resources/views/components/menu/index.blade.php -->
@php
    \Stillat\Dagger\component()->props(['color' => 'gray']);
@endphp

<ul {{ $attributes->merge(['class' => 'bg-'.$color.'-200']) }}>
    {{ $slot }}
</ul>