<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('forwarded named slots can be cached within dynamic components', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 3; $i++)
<c-dynamic-component component="cache.forwarded_cached_slot_root">
    <c-slot:inner.header class="header class" :$title that="other stuff">
        The Header Slot Content! {{ $i }}
    </c-slot:inner.header>
</c-dynamic-component>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
<div class="header class" that="other stuff">
Var: 1
Start
Header: The Header Slot Content! 0

End
</div><div class="header class" that="other stuff">
Var: 1
Start
Header: The Header Slot Content! 1

End
</div><div class="header class" that="other stuff">
Var: 1
Start
Header: The Header Slot Content! 2

End
</div>
EXPECTED;

    $this->assertSame($expected, $this->render($template, ['title' => 'something']));
    \Stillat\Dagger\Tests\StaticTestHelpers::resetCounter();
    $this->assertSame($expected, $this->render($template, ['title' => 'something']));
});

test('test cached forwarded slots receive outer component variable within dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component component="cache.forwarded_cached_slot_root">
    <c-slot:inner.header class="header class" :$title that="other stuff">
        The Header Slot Content! 1 {{ $component->name }}
    </c-slot:inner.header>
</c-dynamic-component>
BLADE;

    $expected = <<<'EXPECTED'
<div class="header class" that="other stuff">
Var: 1
Start
Header: The Header Slot Content! 1 cache.forwarded_cached_slot_root

End
</div>
EXPECTED;

    $this->assertSame($expected, $this->render($template, ['title' => 'something']));
    $this->assertSame($expected, $this->render($template, ['title' => 'something']));
});
