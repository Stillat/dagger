<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('invalid template end component triggers error', function () {
    $this->expectExceptionMessage('Compiler component [compiler:template_end] must be the last component.');

    $template = <<<'BLADE'
<div>
    <c-compiler:template_end />
</div>
BLADE;

    $this->render($template);
});
