@php
use function Stillat\Dagger\component;

component()
    ->props(['valueOne'])
    ->mixin(\Stillat\Dagger\Tests\Mixins\MixinOne::class)
    ->trimOutput();
@endphp

{{ $valueOne }}