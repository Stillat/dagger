<?php

use Illuminate\Support\Str;
use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('default stencil content is rendered if not changed', function () {
    $this->assertSame(
        'Before The Default. After',
        Str::squish($this->render('<c-stencils.basic />'))
    );

    $this->assertSame(
        'Before The Default. After',
        Str::squish($this->render('<c-stencils.basic></c-stencils.basic>'))
    );
});

test('default stencil content can be changed', function () {
    $template = <<<'BLADE'
<c-stencils.basic>
    <c-stencil:the_name>
        Some changed content!
    </c-stencil:the_name>
</c-stencils.basic>
BLADE;

    $this->assertSame(
        'Before Some changed content! After',
        Str::squish($this->render($template))
    );
});

test('default content be included', function () {
    $template = <<<'BLADE'
<c-stencils.basic>
    <c-stencil:the_name>
        <c-stencil:the_name.default />
        Some changed content!
        <c-stencil:the_name.default />
    </c-stencil:the_name>
</c-stencils.basic>
BLADE;

    $this->assertSame(
        'Before The Default. Some changed content! The Default. After',
        Str::squish($this->render($template))
    );
});

test('it compiles multiple stencils', function () {
    $template = <<<'BLADE'
<c-stencils.multiple>
    <c-stencil:stencil_two>
        Updated Second.
        <c-stencil:stencil_two.default />
    </c-stencil:stencil_two>
    
    <c-stencil:stencil_one>
    
        <c-stencil:stencil_one.default />
        Updated First.
        <c-stencil:stencil_one.default />
    </c-stencil:stencil_one>
</c-stencils.multiple>
BLADE;

    $this->assertSame(
        'First The First Stencil Default Updated First. The First Stencil Default Middle Updated Second. The Second Stencil Default Last',
        Str::squish($this->render($template))
    );
});

test('stencil defaults can be mixed', function () {
    $template = <<<'BLADE'
<c-stencils.multiple>
    <c-stencil:stencil_two>
        Inner Before
        <c-stencil:stencil_one.default />
        Updated Second.
        <c-stencil:stencil_two.default />
        <c-stencil:stencil_one.default />
        Inner After
    </c-stencil:stencil_two>
</c-stencils.multiple>
BLADE;

    $expected = <<<'EXPECTED'
First
The First Stencil Default
Middle
Inner Before
        The First Stencil Default
        Updated Second.
        The Second Stencil Default
        The First Stencil Default
        Inner After
Last
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('stencils work with dot syntax', function () {
    $template = <<<'BLADE'
<c-stencils.basic>
    <c-stencil.the_name>
        <c-stencil.the_name.default />
        Some changed content!
        <c-stencil:the_name.default />
    </c-stencil.the_name>
</c-stencils.basic>
BLADE;

    $this->assertSame(
        'Before The Default. Some changed content! The Default. After',
        Str::squish($this->render($template))
    );
});
