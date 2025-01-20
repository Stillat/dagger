@php
use function Stillat\Dagger\component;
use Stillat\Dagger\Tests\StaticTestHelpers;

component()->cache();
@endphp

<div {{ $slots->header->attributes }}>
Var: {{ StaticTestHelpers::counter() }}
Start
Header: {{ $slots->header ?? 'No Header' }}
{{ $slot ?? '' }}
End
</div>