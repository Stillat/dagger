@php
use function Stillat\Dagger\component;
use \Stillat\Dagger\Tests\Mixins\MixinOne;
use \Stillat\Dagger\Tests\Mixins\MixinTwo;

component()->mixin([
    MixinOne::class,
    MixinTwo::class,
])->trimOutput();

@endphp

{{ $valueOne }}
{{ $valueTwo }}
{{ $valueThree }}

