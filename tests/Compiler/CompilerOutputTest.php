<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('it trims dynamic output', function () {
    $template = <<<'BLADE'
<c-output.dynamic_trim />
BLADE;

    $this->assertSame(
        'No Title!',
        $this->render($template)
    );
});

test('it trims static output', function () {
    $template = <<<'BLADE'
<c-output.static_trim />
BLADE;

    $this->assertSame(
        'I am static output!',
        $this->compile($template)
    );

    $this->assertSame(
        'I am static output!',
        $this->render($template)
    );
});
