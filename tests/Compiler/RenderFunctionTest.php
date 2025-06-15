<?php

test('render function is evaluated for each invocation', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 10; $i++)
<c-render.basic />
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Non Render: 1
Render: 2
Non Render: 1
Render: 3
Non Render: 1
Render: 4
Non Render: 1
Render: 5
Non Render: 1
Render: 6
Non Render: 1
Render: 7
Non Render: 1
Render: 8
Non Render: 1
Render: 9
Non Render: 1
Render: 10
Non Render: 1
Render: 11
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template),
    );
});
