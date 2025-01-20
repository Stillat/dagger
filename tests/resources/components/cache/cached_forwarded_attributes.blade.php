@php
use function Stillat\Dagger\component;

component()->props(['title'])->cache();
@endphp

Title: {{ $title }}
Count: {{ \Stillat\Dagger\Tests\StaticTestHelpers::counter() }}
