@php
use function Stillat\Dagger\component;

component()
    ->mixin(\Stillat\Dagger\Tests\Mixins\MixinOne::class)
    ->trimOutput();
@endphp

<c-mixins.aware_child />