<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('it applies mixin data', function () {
    $this->assertSame(
        'Value from mixin one',
        $this->render('<c-mixins.basic />')
    );
});

test('it applies multiple mixins', function () {
    $expected = <<<'EXPECTED'
Value from mixin two
Value two
Value three from mixin two
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-mixins.multiple />')
    );
});

test('it resolves used namespace references when applying mixins', function () {
    $expected = <<<'EXPECTED'
Value from mixin two
Value two
Value three from mixin two
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-mixins.used_namespaces />')
    );
});

test('props override mixin data', function () {
    $this->assertSame(
        'Value from mixin one',
        $this->render('<c-mixins.override />'),
    );

    $this->assertSame(
        'some value',
        $this->render('<c-mixins.override value-one="some value" />')
    );
});

test('mixins can receive component instance', function () {
    $this->assertSame(
        'mixins.receives_component::the suffix',
        $this->render('<c-mixins.receives_component />')
    );
});

test('mixin methods can be used as variables and called on the component', function () {
    $expected = <<<'EXPECTED'
mixins.methods::called from variable
mixins.methods::called on component
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-mixins.methods />')
    );
});

test('aware directive can pull from mixin data', function () {
    $this->assertSame(
        'Value from mixin one',
        $this->render('<c-mixins.aware_root />')
    );
});

test('prop validation can use mixin data', function () {
    $this->assertSame(
        'Value from mixin one',
        $this->render('<c-mixins.validate />')
    );
});

test('mixin data does not get added to attributes', function () {
    $this->assertSame(
        'class="mt-5"',
        $this->render('<c-mixins.attributes class="mt-5" />')
    );
});
