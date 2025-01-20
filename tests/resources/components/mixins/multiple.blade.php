@php
use function Stillat\Dagger\component;

component()->mixin([
    \Stillat\Dagger\Tests\Mixins\MixinOne::class,
    \Stillat\Dagger\Tests\Mixins\MixinTwo::class,
])->trimOutput();
@endphp

{{ $valueOne }}
{{ $valueTwo }}
{{ $valueThree }}

