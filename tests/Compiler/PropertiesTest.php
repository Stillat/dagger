<?php

test('it parses props directive', function () {
    $template = <<<'BLADE'
<c-properties.basic title="The Title" class="some class names here" />
BLADE;

    $expected = <<<'EXPECTED'
<div class="some class names here">
    The Title
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('it compiles default props', function () {
    $template = <<<'BLADE'
<c-properties.default />
BLADE;

    $this->assertSame(
        'The Default Title',
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-properties.default title="The Custom Title" />
BLADE;

    $this->assertSame(
        'The Custom Title',
        $this->render($template)
    );
});

test('it compiles hyphenated props', function () {
    $template = <<<'BLADE'
<c-properties.hyphens the-prop="Some Value" />
BLADE;

    $this->assertSame(
        'Some Value',
        $this->render($template)
    );
});
