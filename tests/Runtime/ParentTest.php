<?php

test('Blade components can be a parent', function () {
    $expected = <<<'EXPECTED'
Using class properties:
The Parent Title from parent
A different title! from child

Using helpers:
The Parent Title from parent
A different title! from child
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<x-parent title="The Parent Title" />')
    );
});
