<?php

use Illuminate\Support\Str;

test('it renders dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component component="dynamic.component_a" title="The Title" />

<c-dynamic-component component="dynamic.component_b" title="The Title" />
BLADE;

    $expected = <<<'EXPECTED'
Component A: The Title
Component B: The Title
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('it renders dynamic components with proxy alias', function () {
    $template = <<<'BLADE'
<c-proxy component="dynamic.component_a" title="The Title" />

<c-proxy component="dynamic.component_b" title="The Title" />
BLADE;

    $expected = <<<'EXPECTED'
Component A: The Title
Component B: The Title
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('it renders dynamic components with delegate-component alias', function () {
    $template = <<<'BLADE'
<c-delegate-component component="dynamic.component_a" title="The Title" />

<c-delegate-component component="dynamic.component_b" title="The Title" />
BLADE;

    $expected = <<<'EXPECTED'
Component A: The Title
Component B: The Title
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('it renders dynamic components with slots', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 2; $i++)
<c-dynamic-component component="dynamic.with_slot">
    {{ $i }}
</c-dynamic-component>
@endfor
BLADE;

    $this->assertSame(
        'The Slot: 0The Slot: 1',
        $this->render($template)
    );
});

test('it forwards forwards attributes to dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="attribute_forwarding.component_a"
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

test('multiple parameters are forwarded to dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="attribute_forwarding.component_a"
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

test('nested parameters are forwarded to dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="attribute_forwarding.component_b"
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

test('forwarded values are not persisted on the root dynamic component', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="attribute_forwarding.component_e"
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

test('dynamic variables are forwarded to dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="attribute_forwarding.component_c"
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

test('slots are forwarded to nested components inside dynamic components', function () {
    $template = <<<'BLADE'
<?php
    $variableInsideTheScope = 'The Value.';
?>
<c-dynamic-component
    component="slot_forwarding.component_a"
>
    <c-slot:nested.header>
        I am the slot content. {{ $variableInsideTheScope }}
    </c-slot:nested.header>
</c-dynamic-component>
BLADE;

    $this->assertSame(
        'Component A Start Component B Start I am the slot content. The Value. Component B EndComponent A End',
        Str::squish($this->render($template)),
    );
});

test('multiple forwarded slots are compiled inside dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="slot_forwarding.component_c"
>
    <c-slot:nestedOne.header>
        Header Override One
    </c-slot:nestedOne.header>
    <c-slot:nestedTwo.header>
        Header Override Two
    </c-slot:nestedTwo.header>
    <c-slot:nestedFour.header>
        Header Override Four
    </c-slot:nestedFour.header>
</c-dynamic-component>
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

test('dynamic components compile stencils inside dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component
    component="dynamic.stencil_b"
>
    <c-stencil:the_name>Hello {{ $title }}</c-stencil:the_name>
</c-dynamic-component>

<c-dynamic-component
    component="dynamic.stencil_a"
>
    <c-stencil:the_name>Hello {{ $title }}</c-stencil:the_name>
</c-dynamic-component>
BLADE;

    $expected = <<<'EXPECTED'
Start: Component Two
Hello Variable inside Component Two
End: Component Two
Start: Component One
Hello Variable inside Component One
End: Component One
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
