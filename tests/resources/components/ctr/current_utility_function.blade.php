@php
    use function Stillat\Dagger\{component, current};

    component()->props(['title']);
@endphp

Title: {{ $title }}
Parent: {{ current()->parent()?->name ?? '' }}