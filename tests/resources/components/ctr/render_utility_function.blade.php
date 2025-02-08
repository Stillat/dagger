@php
    use function Stillat\Dagger\{component, render};

    component()->props(['title']);
@endphp

Title: {{ $title }}
Parent: {{ render(time()) }}