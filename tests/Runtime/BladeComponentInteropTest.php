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
