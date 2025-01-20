@php
use function Stillat\Dagger\component;

component()->mixin([
    \Stillat\Dagger\Tests\Mixins\MixinOne::class,
    \Stillat\Dagger\Tests\Mixins\MixinTwo::class,
    \Stillat\Dagger\Tests\Mixins\MixinThree::class,
])
    ->trimOutput();
@endphp

{{ $attributes }}