<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('simple components can be rendered at compile time', function () {
    $this->assertSame(
        '<button>Book Room</button>',
        trim($this->compile('<c-ctr.button text="Book Room" />'))
    );
});

test('rendered and compile time contents are equivalent', function () {
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
        trim($this->render($template))
    );

    $this->assertSame(
        $expected,
        trim($this->compile($template))
    );
});

test('nested components can be compile time rendered', function () {
    $expected = <<<'EXPECTED'
Simple Parent Start

Child: The Title

Child: The Other Title

Simple Parent End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->compile('<c-ctr.parent_simple />')
    );

    $this->assertSame(
        $expected,
        $this->render('<c-ctr.parent_simple />')
    );
});

test('aware props for nested children can be inferred at compile time', function () {
    // The parent "title" prop being defined should satisfy the nested ctr.child_aware value.
    $this->assertSame(
        'Child: The Title',
        $this->compile('<c-ctr.parent_props title="The Title" />')
    );

    // No title prop available. Compiler should not render at compile time.
    $this->assertNotSame(
        'Child: The Title',
        $this->compile('<c-ctr.parent_props />')
    );
});

test('props that are not satisfied at compile time disable ctr', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.parent_unsatisfiable title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.parent_unsatisfiable title="The Title" />')
    );
});

test('components using parent instance method are not rendered', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.parent_access_instance_method title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.parent_access_instance_method title="The Title" />')
    );
});

test('components using parent instance variable are not compile time rendered', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.parent_access_instance_variable title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.parent_access_instance_variable title="The Title" />')
    );
});

test('components using parent utility function are not compile time rendered', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.parent_access_utility_function title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.parent_access_utility_function title="The Title" />')
    );
});

test('components using component utility function are not compile time rendered', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.component_utility_function title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.component_utility_function title="The Title" />')
    );
});

test('components using current utility function are not compile time rendered', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.current_utility_function title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.current_utility_function title="The Title" />')
    );
});

test('components using render utility function are not compile time rendered', function () {
    $this->assertStringNotContainsString(
        'Title: The Title',
        $this->compile('<c-ctr.render_utility_function title="The Title" />')
    );

    $this->assertStringContainsString(
        'Title: The Title',
        $this->render('<c-ctr.render_utility_function title="The Title" />')
    );
});

test('components called with slots are not eligible for ctr', function () {
    $this->assertStringNotContainsString(
        'With Slot: Slot Contents',
        $this->compile('<c-ctr.with_slot>Slot Contents</c-ctr.with_slot>')
    );

    $this->assertStringContainsString(
        'With Slot: Slot Contents',
        $this->render('<c-ctr.with_slot>Slot Contents</c-ctr.with_slot>')
    );
});

test('components that trigger exceptions disable ctr for that component', function () {
    $this->assertStringContainsString(
        'echo e($title)',
        $this->compile('<c-ctr.throws_exception title="The Title" />')
    );
});

test('compiler rendering can escape Blade', function () {
    $expected = <<<'EXPECTED'
<style>
    @media screen {

    }
</style>

The Title: The Title
{{ $title }}
{!! $title !!}
@if
 Yes 
EXPECTED;

    $this->assertSame(
        $expected,
        $this->compile('<c-ctr.escaped title="The Title" />')
    );
});

test('nested compile-time rendered templates do not double escape escaped Blade', function () {
    $expected = <<<'EXPECTED'
{{ $escapedInParent }}
<style>
    @media screen {

    }
</style>

The Title: The First Title
{{ $title }}
{!! $title !!}
@if
 Yes 
<style>
    @media screen {

    }
</style>

The Title: The Second Title
{{ $title }}
{!! $title !!}
@if
 Yes 
EXPECTED;

    $this->assertSame(
        $expected,
        $this->compile('<c-ctr.escaped_parent />')
    );
});

test('compile time rendering respects trim output', function () {
    $this->assertSame(
        'The Title: The Title',
        $this->compile('<c-ctr.trimmed_output title="The Title" />')
    );
});

test('compile time rendering can be disabled via compiler options', function () {
    $compiled = $this->compile('<c-ctr.disabled title="The Title" />');
    $rendered = $this->render('<c-ctr.disabled title="The Title" />');

    $this->assertNotEquals('The Title', $compiled);
    $this->assertEquals('The Title', $rendered);
});

test('compile time rendering skips components with "unsafe" function calls', function () {
    $compiled = $this->compile('<c-ctr.unsafe_calls />');

    $this->assertStringContainsString('e(time());', $compiled);
    $this->assertStringContainsString('e(now());', $compiled);
    $this->assertStringContainsString("e(date('l'));", $compiled);
});

test('compile time rendering skips components with "unsafe" variables', function () {
    $expected = <<<'EXPECTED'
echo e($title . $_GET['something']);
EXPECTED;

    $this->assertStringContainsString(
        $expected,
        $this->compile('<c-ctr.unsafe_vars title="The Title" />'),
    );
});

test('compile time rendering with static methods', function () {
    $this->assertSame(
        'THE TITLE',
        $this->compile('<c-ctr.static_methods title="The Title" />'),
    );
});

test('compile time rendering can be disabled on a class  and re-enabled on a specific method', function () {
    $template = '<c-ctr.disabled_class_enabled_method />';
    $expected = 'Hello, world.';

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->compile($template));
});

test('compile time rendering can be disabled on a class', function () {
    $template = '<c-ctr.disabled_class />';

    $this->assertStringContainsString(
        'echo e(\Stillat\Dagger\Tests\CtrDisabledClass::methodOne());',
        $this->compile($template),
    );

    $this->assertSame('Hello, world.', $this->render($template));
});

test('compile time rendering can be enabled on a class', function () {
    $template = '<c-ctr.enabled_class />';
    $expected = 'Hello, world.';

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->compile($template));
});

test('compile time rendering can be enabled on a class and disabled on a specific method', function () {
    $template = '<c-ctr.enabled_class_disabled_method />';

    $this->assertStringContainsString(
        'echo e(\Stillat\Dagger\Tests\CtrEnabledClass::methodTwo());',
        $this->compile($template),
    );

    $this->assertSame('Hello, world.', $this->render($template));
});
