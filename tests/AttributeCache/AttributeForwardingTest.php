<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('forwarded attributes can be cached', function () {
    $template = <<<'BLADE'
<c-cache.root #inner:title="The Title" />
<c-cache.root #inner:title="The Title" />
<c-cache.root #inner:title="A Whole New Title" />
BLADE;

    $expected = <<<'EXPECTED'
Title: The Title
Count: 1

Title: The Title
Count: 1

Title: The Title
Count: 1Title: The Title
Count: 1

Title: The Title
Count: 1

Title: The Title
Count: 1Title: A Whole New Title
Count: 2

Title: A Whole New Title
Count: 2

Title: A Whole New Title
Count: 2
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
