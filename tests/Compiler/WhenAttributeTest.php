<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('when attribute is compiled', function () {
    $blade = <<<'BLADE'
<c-when.basic #when="$value" title="The Title" />
BLADE;

    $this->assertSame('', $this->render($blade, ['value' => false]));
    $this->assertSame('The Title', $this->render($blade, ['value' => true]));
});
