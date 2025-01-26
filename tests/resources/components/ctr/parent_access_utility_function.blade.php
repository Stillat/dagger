@php
    use function Stillat\Dagger\{component, _parent};

    component()->props(['title']);
@endphp

Title: {{ $title }}
Parent: {{ _parent()?->name ?? '' }}