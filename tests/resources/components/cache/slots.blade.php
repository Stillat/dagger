@php
use function Stillat\Dagger\component;

component()->cache();
@endphp

Start
{{ $slot ?? '' }}
End