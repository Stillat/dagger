<?php

test('invalid props triggers exception', function () {
    $this->expectExceptionMessage('The title property is required.');
    $this->render('<c-validation.basic />');
});

test('components with validation actually render if given valid data', function () {
    $this->assertSame(
        'The Title',
        $this->render('<c-validation.basic title="The Title" />')
    );
});

test('validation can use mixin data', function () {
    $this->assertSame(
        'Value from mixin one',
        $this->render('<c-validation.mixin />')
    );
});

test('validation can use aware data', function () {
    $this->assertSame(
        'validation.aware_child: The Root Title',
        $this->render('<c-validation.aware_root title="The Root Title" />')
    );
});

test('validation messages can be customized', function () {
    $this->expectExceptionMessage('A custom message for title.');
    $this->render('<c-validation.custom_message />');
});

test('shorthand validation definition', function () {
    $this->expectExceptionMessage('The title property is required. The size property must not be greater than 4.');
    $this->render('<c-validation.shorthand />');
});

test('validation works with forwarded attributes', function () {
    $template = <<<'BLADE'
<c-validation.forward_root
    #theComponent:title="A Forwarded Title"
/>
BLADE;

    $this->assertSame(
        'A Forwarded Title',
        $this->render($template)
    );
});
