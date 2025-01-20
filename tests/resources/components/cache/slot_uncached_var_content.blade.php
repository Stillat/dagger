@php
use function Stillat\Dagger\component;
use Stillat\Dagger\Tests\StaticTestHelpers;

component();
@endphp

Var: {{ StaticTestHelpers::counter() }}
Start
{{ $slot ?? '' }}
End