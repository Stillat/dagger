@php
    use function Stillat\Dagger\{component};

    component()->props(['title']);
@endphp

Title: {{ $title }}
Parent: {{ component()->parent()?->name ?? '' }}