<?php

test('it compiles simple static content', function () {
    $this->assertSame(
        'Some Static Content {{ title }}',
        $this->compile('<c-static />')
    );
});

test('fully qualified component function works', function () {
    $expected = <<<'EXPECTED'
Title: The Title
Attributes: class="something"
Var: The Value
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-component_function title="The Title" class="something" />')
    );
});

test('it compiles different styles of delimiters', function () {
    $this->assertSame('Some Static Content {{ title }}', $this->compile('<c-static />'));
    $this->assertSame('Some Static Content {{ title }}', $this->compile('<c:static />'));
    $this->assertSame('Some Static Content {{ title }}', $this->compile('<c:static></c:static>'));
});

test('nested static components dont get compiled again', function () {
    $staticContent = 'Some Static Content {{ title }}';

    $this->assertStringContainsString(
        $staticContent,
        $this->compile('<c-nested_static />')
    );

    $this->assertSame(
        2,
        mb_substr_count(
            $this->compile('<c-nested_static /><c-nested_static />'),
            $staticContent
        )
    );

    $compiled = $this->compile('<c-nested_static /><c-nested_static /><c-static/>');

    $this->assertSame(3, mb_substr_count($compiled, $staticContent));

    $this->assertStringEndsWith(
        $staticContent,
        $compiled
    );
});

test('it resolves views', function () {
    $this->assertSame(
        'I am root',
        $this->render('<c-root />'),
        'Root'
    );

    $this->assertSame(
        'The Title',
        $this->render('<c-sub :$title />', ['title' => 'The Title']),
        'index.blade.php file inside a sub-directory'
    );

    $this->assertSame(
        'Sub Component: The Title',
        $this->render('<c-sub.component :$title />', ['title' => 'The Title']),
        'A .blade.php file inside a sub-directory'
    );
});

test('components can end with php', function () {
    $this->assertSame(
        'cool',
        $this->render('<c-end_php />')
    );
});

test('index components are rendered if they have the same name as their directory', function () {
    $this->assertSame(
        'I am the accordion.',
        $this->render('<c-accordion />')
    );
});
