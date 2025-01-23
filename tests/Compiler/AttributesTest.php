<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('it renders attributes', function () {
    $this->assertSame(
        'class="one two three" No Prop',
        $this->render('<c-attributes class="one two three" />', ['prop_one' => 'The Prop Value'])
    );

    $this->assertSame(
        'class="one two three" The Prop Value',
        $this->render('<c-attributes class="one two three" prop-one="The Prop Value" />', ['prop_one' => 'The Prop Value'])
    );
});

test('compiler prefixed attributes are not passed to components', function () {
    $template = <<<'BLADE'
<c-attribute_component #id="aComponentName" class="one" />
BLADE;

    $this->assertSame(
        'Attributes: class="one"',
        $this->render($template)
    );
});

test('escaped compiler prefixed attributes can be passed to components', function () {
    $template = <<<'BLADE'
<c-attribute_component ##id="aComponentName" class="one" />
BLADE;

    $this->assertSame(
        'Attributes: #id="aComponentName" class="one"',
        $this->render($template)
    );
});

test('attributes become data if no props are defined', function () {
    $template = <<<'BLADE'
<c-properties.attributes_become_data_if_no_props testvar="Test Value" />
BLADE;

    $this->assertSame(
        '<div>Test Value</div>',
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-properties.attributes_become_data_if_no_props />
BLADE;

    $this->assertSame(
        '<div></div>',
        $this->render($template)
    );
});

test('attributes do not become data if props are defined', function () {
    $template = <<<'BLADE'
<c-properties.attributes_become_attributes_if_props_defined testvar="The Test Value" />
BLADE;

    $this->assertSame(
        '<div>No Value</div>',
        $this->render($template)
    );
});

test('hyphenated attributes are not case converted', function () {
    $this->assertSame(
        '<div data-thing="the thing">The Title</div>',
        $this->render('<c-button data-thing="the thing" title="The Title" />')
    );
});

test('attribute passing', function () {
    $daggerTemplate = <<<'BLADE'
<c-attribute_passing_root class="mt-4" data-foo="bar" />
BLADE;

    $bladeTemplate = <<<'BLADE'
<x-attribute_passing_root class="mt-4" data-foo="bar" />
BLADE;

    $expected = <<<'EXPECTED'
Root: Child: data-thing="value" class="mt-4" data-foo="bar"After Child: class="mt-4" data-foo="bar"
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($daggerTemplate)
    );

    $this->assertSame(
        $expected,
        $this->render($bladeTemplate)
    );
});
