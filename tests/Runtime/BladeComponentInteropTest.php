<?php

use Illuminate\Support\Str;

uses(\Stillat\Dagger\Tests\CompilerTestCase::class);

test('it compiles content inside Blade component slots', function () {
    $template = <<<'BLADE'
<x-alert>
    <c-static />
</x-alert>
BLADE;

    $this->assertSame(
        'Blade component test. Some Static Content {{ title }}',
        $this->render($template)
    );
});

test('it can receive props from Blade components using aware', function () {
    $template = <<<'BLADE'
<x-interop_root title="The Root Title" />
BLADE;

    $this->assertSame(
        'Nested Start The Root Title Nested End',
        Str::squish($this->render($template))
    );
});

test('Blade components can receive values from Dagger components using aware', function () {
    $template = <<<'BLADE'
<c-interop_root title="The Root Title" />
BLADE;

    $this->assertSame(
        'Nested Start The Title: The Root TitleNested End',
        Str::squish($this->render($template))
    );
});

test('compiler injects stack behaviors into multi-line Blade tags', function () {
    $template = <<<'BLADE'
<x-alert :thing="[
    'multi' => 'line',
    'test' => 'here'
]" />
BLADE;

    $this->assertSame(
        'Blade component test. ',
        $this->render($template)
    );
});

test('Blade component stacks', function () {
    $expected = <<<'BLADE'
Component B: The Title
Component C: The Title
Component E: The Title Nope
BLADE;

    $this->assertSame(
        $expected,
        $this->render('<x-component_a title="The Title" /> <x-component_f />')
    );
});

test('Dagger components inside Blade slot content', function () {
    $template = <<<'BLADE'
<x-parent_with_slot>
    <c-basic title="The Title" />
</x-parent_with_slot>

<c-basic title="Another Title" />
BLADE;

    $expected = <<<'EXPECTED'
<div id="theParent">
    The Title
</div>
Another Title
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('stencils inside Blade slot content', function () {
    $template = <<<'BLADE'
<x-parent_with_slot>
<c-stencils.basic>
    <c-stencil:the_name>
        Some changed content!
    </c-stencil:the_name>
</c-stencils.basic>
</x-parent_with_slot>

<c-stencils.basic />

<c-stencils.basic>
    <c-stencil:the_name>
        Some changed content #1
        <x-parent_with_slot>
        <c-stencils.basic>
            <c-stencil:the_name>
                Some changed content #2
            </c-stencil:the_name>
        </c-stencils.basic>
        </x-parent_with_slot>
    </c-stencil:the_name>
</c-stencils.basic>

<c-stencils.basic />

BLADE;

    $expected = <<<'EXPECTED'
<div id="theParent">
    Before
Some changed content!
After
</div>
Before
The Default.
After

Before
Some changed content #1
        <div id="theParent">
    Before
Some changed content #2
After
</div>After
Before
The Default.
After

EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
