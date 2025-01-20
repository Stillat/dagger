@php
    use function Stillat\Dagger\component;

    $component = component()
        ->mixin(\Stillat\Dagger\Tests\Mixins\MixinThree::class)
        ->trimOutput();
@endphp

{{ $testMethod('called from variable') }}
{{ $component->testMethod('called on component') }}