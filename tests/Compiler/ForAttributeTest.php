<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('for attribute is compiled', function () {
    $template = <<<'BLADE'
<ul>
    <c-for.item #for.items.item :text="$item['text']" />
</ul>
BLADE;

    $data = [
        'items' => [
            ['text' => 'Item 1'],
            ['text' => 'Item 2'],
        ],
    ];

    $expected = <<<'EXPECTED'
<ul>
    

<li>Item 1</li>

<li>Item 2</li></ul>
EXPECTED;

    $this->assertSame($expected, $this->render($template, $data));
});

test('alias does not leak and stomp over existing data', function () {
    $template = <<<'BLADE'
<?php $item = 'Hello, world.'; ?>
<ul>
    <c-for.item #for.items.item :text="$item['text']" />
</ul>
After: {{ $item }}
BLADE;

    $data = [
        'items' => [
            ['text' => 'Item 1'],
            ['text' => 'Item 2'],
        ],
    ];

    $expected = <<<'EXPECTED'
<ul>
    

<li>Item 1</li>

<li>Item 2</li></ul>
After: Hello, world.
EXPECTED;

    $this->assertSame($expected, $this->render($template, $data));
});

test('the variable can be injected as a prop', function () {

    $template = <<<'BLADE'
<ul>
    <c-for.item_prop #for.items.$item />
</ul>
BLADE;

    $data = [
        'items' => [
            ['text' => 'Item 1'],
            ['text' => 'Item 2'],
        ],
    ];

    $expected = <<<'EXPECTED'
<ul>
    

<li>Item 1</li>

<li>Item 2</li></ul>
EXPECTED;

    $this->assertSame($expected, $this->render($template, $data));
});

test('variables can be spread into component props', function () {

    $template = <<<'BLADE'
<ul>
    <c-for.item_spread #for.items />
</ul>
BLADE;

    $data = [
        'items' => [
            ['text' => 'Item 1', 'id' => 'test-id'], // The id should not leak as an extra attribute.
            ['text' => 'Item 2', 'id' => 'test-id'], // The id should not leak as an extra attribute.
        ],
    ];

    $expected = <<<'EXPECTED'
<ul>
    

<li >Item 1</li>

<li >Item 2</li></ul>
EXPECTED;

    $this->assertSame($expected, $this->render($template, $data));
});

test('variables can be spread into component props using explicit syntax', function () {
    $template = <<<'BLADE'
<ul>
    <c-for.item_spread #for.$items.item />
</ul>
BLADE;

    $data = [
        'items' => [
            ['text' => 'Item 1', 'id' => 'test-id'], // The id should not leak as an extra attribute.
            ['text' => 'Item 2', 'id' => 'test-id'], // The id should not leak as an extra attribute.
        ],
    ];

    $expected = <<<'EXPECTED'
<ul>
    

<li >Item 1</li>

<li >Item 2</li></ul>
EXPECTED;

    $this->assertSame($expected, $this->render($template, $data));
});

test('values can be spread using the attribute syntax', function () {
    $template = <<<'BLADE'
<ul>
    <c-for.item_spread #for="$items as ...$item" />
</ul>
BLADE;

    $data = [
        'items' => [
            ['text' => 'Item 1', 'id' => 'test-id'], // The id should not leak as an extra attribute.
            ['text' => 'Item 2', 'id' => 'test-id'], // The id should not leak as an extra attribute.
        ],
    ];

    $expected = <<<'EXPECTED'
<ul>
    

<li >Item 1</li>

<li >Item 2</li></ul>
EXPECTED;

    $this->assertSame($expected, $this->render($template, $data));
});
