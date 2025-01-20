<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('it parses basic component props directive', function () {
    $template = <<<'BLADE'
@props(['title', 'type'])
BLADE;

    $this->assertSame(
        ['title', 'type'],
        $this->parseComponent($template)->getPropNames(),
    );
});

test('it parses component props directive with defaults', function () {
    $template = <<<'BLADE'
@props(['title', 'type' => 'error'])
BLADE;

    $this->assertSame(
        ['title', 'type'],
        $this->parseComponent($template)->getPropNames(),
    );
});

test('it parses basic component aware directive', function () {
    $template = <<<'BLADE'
@aware(['title', 'type'])
BLADE;

    $this->assertSame(
        ['title', 'type'],
        $this->parseComponent($template)->getAwareVariables(),
    );
});

test('it parses component aware directive with defaults', function () {
    $template = <<<'BLADE'
@aware(['title' => 'a default title!', 'type' => 'error'])
BLADE;

    $this->assertSame(
        ['title', 'type'],
        $this->parseComponent($template)->getAwareVariables()
    );
});

test('it only returns root keys for aware directive', function () {
    $template = <<<'BLADE'
@aware(['title' => 'a default title!', 'type' => ['nested', 'keys']])
BLADE;

    $this->assertSame(
        ['title', 'type'],
        $this->parseComponent($template)->getAwareVariables()
    );
});

test('it only returns root keys for props directive', function () {
    $template = <<<'BLADE'
@props(['title' => 'a default title!', 'type' => ['nested', 'keys']])
BLADE;

    $this->assertSame(
        ['title', 'type'],
        $this->parseComponent($template)->getPropNames()
    );
});

test('it adjusts casing of prop names using props directive', function () {
    $template = <<<'BLADE'
@props(['propName' => 'default', 'anotherPropName'])

BLADE;

    $this->assertSame(
        [
            'propName', 'prop-name',
            'anotherPropName', 'another-prop-name',
        ],
        $this->parseComponent($template)->getPropNames()
    );
});

test('it parses defaults when using props directive', function () {
    $template = <<<'BLADE'
@props(['propName' => 'default', 'propName2' => 42.42, 'propName3' => ['one', 'two', 'three']])
BLADE;

    $defaults = $this->parseComponent($template)->getPropDefaults();

    $this->assertArrayHasKey('propName', $defaults);
    $this->assertArrayHasKey('propName2', $defaults);
    $this->assertArrayHasKey('propName3', $defaults);

    $this->assertSame("'default'", $defaults['propName']);
    $this->assertSame('42.42', $defaults['propName2']);
    $this->assertSame("['one', 'two', 'three']", $defaults['propName3']);
});

test('it parses defaults when using aware directive', function () {
    $template = <<<'BLADE'
@aware(['propName' => 'default', 'propName2' => 42.42, 'propName3' => ['one', 'two', 'three']])
BLADE;

    $defaults = $this->parseComponent($template)->getAwareDefaults();

    $this->assertArrayHasKey('propName', $defaults);
    $this->assertArrayHasKey('propName2', $defaults);
    $this->assertArrayHasKey('propName3', $defaults);

    $this->assertSame("'default'", $defaults['propName']);
    $this->assertSame('42.42', $defaults['propName2']);
    $this->assertSame("['one', 'two', 'three']", $defaults['propName3']);
});

test('it parses defaults when using aware and props directives', function () {
    $template = <<<'BLADE'
@props(['propName' => 'default2', 'propName2' => 18.32, 'propName3' => ['one', 'two', 'three', 'four' => ['five']]])
@aware(['propName4' => 'default', 'propName5' => 42.42, 'propName6' => ['one', 'two', 'three']])
BLADE;

    $component = $this->parseComponent($template);

    $propsDefaults = $component->getPropDefaults();

    $this->assertArrayHasKey('propName', $propsDefaults);
    $this->assertArrayHasKey('propName2', $propsDefaults);
    $this->assertArrayHasKey('propName3', $propsDefaults);

    $this->assertSame("'default2'", $propsDefaults['propName']);
    $this->assertSame('18.32', $propsDefaults['propName2']);
    $this->assertSame("['one', 'two', 'three', 'four' => ['five']]", $propsDefaults['propName3']);

    $awareDefaults = $component->getAwareDefaults();

    $this->assertArrayHasKey('propName4', $awareDefaults);
    $this->assertArrayHasKey('propName5', $awareDefaults);
    $this->assertArrayHasKey('propName6', $awareDefaults);

    $this->assertSame("'default'", $awareDefaults['propName4']);
    $this->assertSame('42.42', $awareDefaults['propName5']);
    $this->assertSame("['one', 'two', 'three']", $awareDefaults['propName6']);
});

test('it defaults when using component builder method', function () {
    $template = <<<'BLADE'
<?php
use function Stillat\Dagger\component;

component()->props([
    'propName' => 'default2',
    'propName2' => 18.32,
    'propName3' => [
        'one', 'two', 'three', 'four' => ['five'],    
    ]
])->aware([
    'propName4' => 'default',
    'propName5' => 42.42,
    'propName6' => ['one', 'two', 'three']
]);

?>

BLADE;

    $component = $this->parseComponent($template);

    $propsDefaults = $component->getPropDefaults();

    $this->assertArrayHasKey('propName', $propsDefaults);
    $this->assertArrayHasKey('propName2', $propsDefaults);
    $this->assertArrayHasKey('propName3', $propsDefaults);

    $this->assertSame("'default2'", $propsDefaults['propName']);
    $this->assertSame('18.32', $propsDefaults['propName2']);
    $this->assertSame("['one', 'two', 'three', 'four' => ['five']]", $propsDefaults['propName3']);

    $awareDefaults = $component->getAwareDefaults();

    $this->assertArrayHasKey('propName4', $awareDefaults);
    $this->assertArrayHasKey('propName5', $awareDefaults);
    $this->assertArrayHasKey('propName6', $awareDefaults);

    $this->assertSame("'default'", $awareDefaults['propName4']);
    $this->assertSame('42.42', $awareDefaults['propName5']);
    $this->assertSame("['one', 'two', 'three']", $awareDefaults['propName6']);
});
