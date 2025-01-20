@php
use function Stillat\Dagger\component;

component()->cache();
@endphp

Content {{ $slot ?? 'No Slot' }}