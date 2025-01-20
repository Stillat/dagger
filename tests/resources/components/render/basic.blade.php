@php
use function Stillat\Dagger\{component, render};
use Stillat\Dagger\Tests\StaticTestHelpers;

component()->cache();
@endphp

Non Render: {{ StaticTestHelpers::counter() }}
Render: {{ render(StaticTestHelpers::counter()) }}