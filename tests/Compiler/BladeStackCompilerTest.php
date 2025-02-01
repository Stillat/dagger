<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('it does not trigger errors if it cannot find ending string', function () {
    $input = <<<'PHP'
@props([
    'icon' => null,
])

##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'component::' . $dynamicComponentName, [
])
<?php $component->withAttributes($attributes->getAttributes()); ?>{{ $slot }} @endComponentClass##END-COMPONENT-CLASS##
PHP;

    $this->assertSame(
        $input,
        $this->compileStacks($input)
    );
});
