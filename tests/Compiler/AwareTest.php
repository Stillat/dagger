<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('aware can retrieve default prop value from parent', function () {
    $this->assertSame(
        'Root Default!',
        $this->render('<c-aware.root_one />')
    );
});

test('aware can have defaults', function () {
    $this->assertSame(
        'The color: gray',
        $this->render('<c-aware.default />')
    );
});
