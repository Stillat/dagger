@php
use function Stillat\Dagger\component;

component()->props(['valueOne'])->aware(['valueOne']);
@endphp

{{ $valueOne }}