<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('aware can retrieve default prop value from parent', function () {
    $this->assertSame(
        'Root Default!',
        $this->render('<c-aware.root_one />')
    );
});

test('aware can have defaults', function () {
    $this->assertSame(
        'The color: gray',
        $this->render('<c-aware.default />')
    );
});

test('aware removes items from the attribute bag', function () {
    $template = <<<'BLADE'
<c-aware_attributes.menu color="purple">
    <c-aware_attributes.menu.item>...</c-aware_attributes.menu.item>
    <c-aware_attributes.menu.item color="red">...</c-aware_attributes.menu.item>
</c-aware_attributes.menu>
BLADE;

    $expected = <<<'EXPECTED'
<div class="bg-purple-200">
    

<li class="text-purple-800">...</li>    

<li class="text-red-800">...</li>
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('aware removes items from the attribute bag when supplied with default values', function () {
    $template = <<<'BLADE'
<c-aware_attributes.menu color="purple">
    <c-aware_attributes.menu.item_default>...</c-aware_attributes.menu.item_default>
    <c-aware_attributes.menu.item_default color="red">...</c-aware_attributes.menu.item_default>
</c-aware_attributes.menu>
BLADE;

    $expected = <<<'EXPECTED'
<div class="bg-purple-200">
    

<li class="text-purple-800">...</li>    

<li class="text-red-800">...</li>
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
