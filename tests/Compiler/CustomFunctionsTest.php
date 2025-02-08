<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('custom functions in a component are compiled', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 5; $i++)
<c-functions.declared title="The Title: {{ $i }}" />
@endfor
BLADE;

    $expected = <<<'EXPECTED'
THE TITLE: 0THE TITLE: 1THE TITLE: 2THE TITLE: 3THE TITLE: 4
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
