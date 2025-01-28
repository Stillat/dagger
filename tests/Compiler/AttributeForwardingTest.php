<?php

use Illuminate\Support\Str;
use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('parameters can be forwarded', function () {
    $template = <<<'BLADE'
<c-attribute_forwarding.panel />
BLADE;

    $this->assertSame(
        'Panel Start No Title Panel End',
        Str::squish($this->render($template))
    );

    $template = <<<'BLADE'
<c-attribute_forwarding.panel #theButton:title="The New Title Value" />
BLADE;

    $this->assertSame(
        'Panel Start The New Title Value Panel End',
        Str::squish($this->render($template))
    );
});

test('multiple parameters can be forwarded', function () {
    $template = <<<'BLADE'
<c-attribute_forwarding.component_a
    #buttonOne:title="Button One Title"
    #buttonThree:title="Button Three Title"
    #buttonTwo:title="Button Two Title"
/>
BLADE;

    $expected = <<<'EXPECTED'
Button One Title

Button Two Title

Button Three Title
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('nested parameters can be forwarded', function () {
    $template = <<<'BLADE'
<c-attribute_forwarding.component_b
    #componentA.buttonThree:title="The Third Button"
    #componentB.buttonTwo:title="Component B Button Two"
    #componentA.buttonOne:title="The First Button"
    #componentB.buttonThree:title="Component B Button Three"
    #componentA.buttonTwo:title="The Second Button"
/>
BLADE;

    $expected = <<<'EXPECTED'
Component B Start



The First Button

The Second Button

The Third Button

No Title

Component B Button Two

Component B Button Three
Component B End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('forwarded values do not persist on the root component', function () {
    $template = <<<'BLADE'
<c-attribute_forwarding.component_e
    #theComponent:title="The Title"
/>
BLADE;

    $expected = <<<'EXPECTED'
<div >
    The Title
</div>

EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('attributes can be forwarded', function () {
    $template = <<<'BLADE'
<c-attribute_forwarding.component_c
    #theComponent:title="The Title"
    #theComponent:class="class one two three"
    #theComponent:readonly
/>
BLADE;

    $expected = <<<'EXPECTED'
<div class="class one two three" readonly="readonly">
    The Title
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('dynamic variables can be forwarded', function () {
    $template = <<<'BLADE'
<c-attribute_forwarding.component_c
    :#theComponent:title="$title"
    #theComponent:class="one two three"
    #theComponent:readonly
/>
BLADE;

    $expected = <<<'EXPECTED'
<div class="one two three" readonly="readonly">
    A fancy title
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template, ['title' => 'A fancy title'])
    );
});
