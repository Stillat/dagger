@php
use function Stillat\Dagger\component;

component()
    ->mixin(\Stillat\Dagger\Tests\Mixins\MixinThree::class)
    ->trimOutput();
@endphp

{{ $testMethod('the suffix') }}