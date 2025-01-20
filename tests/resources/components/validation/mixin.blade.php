@php
    use function Stillat\Dagger\component;

    component()
        ->props(['valueOne'])
        ->validateProps([
            'valueOne' => 'required',
        ])
        ->mixin(\Stillat\Dagger\Tests\Mixins\MixinOne::class)
        ->trimOutput();
@endphp

{{ $valueOne }}