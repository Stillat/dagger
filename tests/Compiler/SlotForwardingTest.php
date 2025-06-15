<?php

use Illuminate\Support\Str;

test('slots can be forward to nested components', function () {
    $template = <<<'BLADE'
<?php
    $variableInsideTheScope = 'The Value.';
?>
<c-slot_forwarding.component_a>
    <c-slot:nested.header>
        I am the slot content. {{ $variableInsideTheScope }}
    </c-slot:nested.header>
</c-slot_forwarding.component_a>
BLADE;

    $this->assertSame(
        'Component A Start Component B Start I am the slot content. The Value. Component B EndComponent A End',
        Str::squish($this->render($template)),
    );
});

test('multiple forwarded slots can be compiled', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_c>
    <c-slot:nestedOne.header>
        Header Override One
    </c-slot:nestedOne.header>
    <c-slot:nestedTwo.header>
        Header Override Two
    </c-slot:nestedTwo.header>
    <c-slot:nestedFour.header>
        Header Override Four
    </c-slot:nestedFour.header>
</c-slot_forwarding.component_c>
BLADE;

    $expected = <<<'EXPECTED'
Nested 1 Start
Component B Start

Header Override One

Component B EndNested 1 End

Nested 2 Start
Component B Start

Header Override Two

Component B EndNested 2 End

Nested 3 Start
Component B Start

No header.

Component B EndNested 3 End

Nested 4 Start
Component B Start

Header Override Four

Component B EndNested 4 End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('nested slots can be forwarded', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_d />
BLADE;

    $expected = <<<'EXPECTED'
Nested 1 Start
Component B Start

Header Override One

Component B EndNested 1 End

Nested 2 Start
Component B Start

Header Override Two

Component B EndNested 2 End

Nested 3 Start
Component B Start

No header.

Component B EndNested 3 End

Nested 4 Start
Component B Start

Header Override Four

Component B EndNested 4 End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('nested component paths can be forwarded', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_e>
    <c-slot:formOne.submit.buttonText>
        The Custom Button Text
    </c-slot:formOne.submit.buttonText>
</c-slot_forwarding.component_e>
BLADE;

    $this->assertSame(
        '<div class="the-form"> <button>The Custom Button Text</button></div>',
        Str::squish($this->render($template))
    );
});

test('properties can be passed with forwarded slots', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_f>
    <c-slot:theComponent.footer class="one two three">
        The Slot Content
    </c-slot:theComponent.footer>
</c-slot_forwarding.component_f>
BLADE;

    $expected = <<<'EXPECTED'
<div class="one two three">
    The Slot Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template),
    );
});

test('forwarded slots override existing slots', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_h>
</c-slot_forwarding.component_h>
BLADE;

    $expected = <<<'EXPECTED'
<div class="one two three four">
    Component H Slot Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-slot_forwarding.component_h>
    <c-slot:theComponent.footer class="one two three">
        The Slot Content
    </c-slot:theComponent.footer>
</c-slot_forwarding.component_h>
BLADE;

    $expected = <<<'EXPECTED'
<div class="one two three">
    The Slot Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template),
    );
});

test('overridden slots are not added to compiled output', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_i>
    <c-slot:theComponent.footer class="one two three">
        The Slot Content
    </c-slot:theComponent.footer>
</c-slot_forwarding.component_i>
BLADE;

    $expected = <<<'EXPECTED'
<div class="one two three">
    The Slot Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('default slot content can be forwarded', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_j></c-slot_forwarding.component_j>
BLADE;

    $this->assertSame(
        'The default slot content.',
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-slot_forwarding.component_j>
    <c-slot:theComponent.default>
        The Changed Content
    </c-slot:theComponent.default>
</c-slot_forwarding.component_j>
BLADE;

    $this->assertSame(
        'The Changed Content',
        $this->render($template)
    );
});

test('forwarded slots receive outer component variable', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_j>
    <c-slot:theComponent.default>
        Forwarded Default: {{ $component->name }}
    </c-slot:theComponent.default>
</c-slot_forwarding.component_j>
BLADE;

    $this->assertSame(
        'Forwarded Default: slot_forwarding.component_j',
        $this->render($template)
    );
});

test('named slots receive outer component variable', function () {
    $template = <<<'BLADE'
<c-slot_forwarding.component_h>
    <c-slot:theComponent.footer class="one two three">
        The Slot Content: {{ $component->name }}
    </c-slot:theComponent.footer>
</c-slot_forwarding.component_h>
BLADE;

    $expected = <<<'EXPECTED'
<div class="one two three">
    The Slot Content: slot_forwarding.component_h
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
