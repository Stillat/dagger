@php
use function Stillat\Dagger\component;

component()->cache();
@endphp


{{ $slots->header ?? 'No Header' }}
Content {{ $slot ?? 'No Slot' }}