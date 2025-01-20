<?php

uses(\Stillat\Dagger\Tests\CompilerTestCase::class);

test('it retrieves depth using component parent', function () {
    $this->assertSame(
        'Depth: 1',
        $this->render('<c-depth.root />')
    );

    $this->assertSame(
        'Depth: 2',
        $this->render('<x-depth_root />')
    );
});
