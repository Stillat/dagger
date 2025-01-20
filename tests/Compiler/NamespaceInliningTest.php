<?php

use Illuminate\Support\Str;
use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('class import namespaces are inlined', function () {
    $template = <<<'BLADE'
@foreach ($items as $item)
    <c-namespaces.class_import :title="$item" />
@endforeach
BLADE;

    $compiled = $this->compile($template);

    $this->assertStringNotContainsString(
        'e(Str::upper($title))',
        $compiled
    );

    $this->assertStringContainsString(
        'e(Illuminate\Support\Str::upper($title))',
        $compiled
    );

    $this->assertSame(
        'ALICE BOB',
        Str::squish($this->render($template, ['items' => ['alice', 'bob']]))
    );
});

test('aliased class imports are inlined', function () {
    $template = <<<'BLADE'
@foreach ($items as $item)
    <c-namespaces.aliased_class_import :title="$item" />
@endforeach
BLADE;

    $compiled = $this->compile($template);

    $this->assertStringNotContainsString(
        'e(SomethingElse::upper($title))',
        $compiled
    );

    $this->assertStringContainsString(
        'e(Illuminate\Support\Str::upper($title))',
        $compiled
    );

    $this->assertSame(
        'ALICE BOB',
        Str::squish($this->render($template, ['items' => ['alice', 'bob']]))
    );
});

test('imported functions are inlined', function () {
    $compiled = $this->compile('<c-namespaces.function_import :$title />');

    $this->assertStringNotContainsString(
        'echo e(myFunction($title))',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\myFunction($title))',
        $compiled
    );
});

test('const imports are inlined', function () {
    $compiled = $this->compile('<c-namespaces.const_import />');

    $this->assertStringNotContainsString(
        'echo e(MY_CONSTANT)',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\MY_CONSTANT);',
        $compiled
    );
});

test('class group use statements are inlined', function () {
    $compiled = $this->compile('<c-namespaces.class_group_use />');

    $this->assertStringContainsString(
        'echo e(Some\Namespace\MyClass::someMethod($title));',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\AnotherClass::anotherMethod($title));',
        $compiled
    );
});

test('function group use statements are inlined', function () {
    $compiled = $this->compile('<c-namespaces.function_group_use />');

    $this->assertStringContainsString(
        'echo e(Some\Namespace\myFunction($title));',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\anotherFunction($title));',
        $compiled
    );
});

test('class aliased group use imports are inlined', function () {
    $compiled = $this->compile('<c-namespaces.class_aliased_group_use />');

    $this->assertStringNotContainsString(
        'AliasedClass',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\MyClass::someMethod($title));',
        $compiled
    );
});

test('function aliased group use imports are inlined', function () {
    $compiled = $this->compile('<c-namespaces.function_aliased_group_use />');

    $this->assertStringNotContainsString(
        'aliasedFunction',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\myFunction($title));',
        $compiled
    );
});

test('mixed imports are inlined', function () {
    $compiled = $this->compile('<c-namespaces.mixed_imports />');

    $this->assertStringContainsString(
        'echo e(Illuminate\Support\Str::upper($title));',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\MyClass::someMethod($title));',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\AnotherClass::anotherMethod($title));',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\myFunction($title));',
        $compiled
    );

    $this->assertStringContainsString(
        'echo e(Some\Namespace\SOME_CONST);',
        $compiled
    );
});
