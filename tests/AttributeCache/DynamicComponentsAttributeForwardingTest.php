<?php

test('forwarded attributes can be cached inside dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component component="cache.root" #inner:title="The Title" />
<c-dynamic-component component="cache.root" #inner:title="The Title" />
<c-dynamic-component component="cache.root" #inner:title="A Whole New Title" />
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

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
});
