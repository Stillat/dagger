@php
    \Stillat\Dagger\component()->props(['title']);

    $myVar = 'The Value';
@endphp

Title: {{ $title }}
Attributes: {{ $attributes }}
Var: {{ $myVar }}