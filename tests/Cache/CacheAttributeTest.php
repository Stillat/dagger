<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('basic cache attribute', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 5; $i++)
<c-cache_attribute.basic
    #cache.30m="key"
    title="The Title"
/>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Title: The Title
Var: 1

Title: The Title
Var: 1

Title: The Title
Var: 1

Title: The Title
Var: 1

Title: The Title
Var: 1
EXPECTED;

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
});

test('forever cache attribute', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 5; $i++)
<c-cache_attribute.basic
    #cache.forever="key"
    title="The Title2"
/>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Title: The Title2
Var: 1

Title: The Title2
Var: 1

Title: The Title2
Var: 1

Title: The Title2
Var: 1

Title: The Title2
Var: 1
EXPECTED;

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
});

test('flexible cache attribute', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 5; $i++)
<c-cache_attribute.basic
    #cache.flexible:10:20="key"
    title="The Title3"
/>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Title: The Title3
Var: 1

Title: The Title3
Var: 1

Title: The Title3
Var: 1

Title: The Title3
Var: 1

Title: The Title3
Var: 1
EXPECTED;

    $compiled = $this->compile($template);

    $this->assertStringContainsString(
        "echo cache()->store('array')->flexible(",
        $compiled
    );

    $this->assertStringContainsString(
        ', [10, 20], function ()',
        $compiled,
    );

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
})->skip();

test('dynamic cache keys', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 5; $i++)
<c-cache_attribute.basic
    :#cache.forever="'key'.$i"
    title="The Title4"
/>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Title: The Title4
Var: 1

Title: The Title4
Var: 2

Title: The Title4
Var: 3

Title: The Title4
Var: 4

Title: The Title4
Var: 5
EXPECTED;

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
});
