@php
    $test = 123;
    \Stillat\Dagger\component()->props(['title'])->trimOutput();

@endphp

{{ $test }}