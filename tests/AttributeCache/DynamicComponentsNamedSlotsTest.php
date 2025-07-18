<?php

test('dynamic named slot variables bust the cache within dynamic components', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 2; $i++)
<c-dynamic-component component="cache.named_slot">
    <c-slot:header class="header class" :$title that="other stuff" :thing="\Stillat\Dagger\Tests\StaticTestHelpers::counter()">
        The Header Slot Content! {{ $i }}
    </c-slot:header>
    
    Default Content! {{ $i }}
</c-dynamic-component>
<c-dynamic-component component="cache.named_slot">
    <c-slot:header class="header class" :$title that="other stuff" :thing="\Stillat\Dagger\Tests\StaticTestHelpers::counter()">
        The Header Slot Content! {{ $i }}
    </c-slot:header>
    
    Default Content! {{ $i }}
</c-dynamic-component>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
<div class="header class" title="something" that="other stuff" thing="1">
    The Header Slot Content! 0
</div>

Default Content! 0
<div class="header class" title="something" that="other stuff" thing="2">
    The Header Slot Content! 0
</div>

Default Content! 0
<div class="header class" title="something" that="other stuff" thing="3">
    The Header Slot Content! 1
</div>

Default Content! 1
<div class="header class" title="something" that="other stuff" thing="4">
    The Header Slot Content! 1
</div>

Default Content! 1
EXPECTED;

    $this->assertSame($expected, $this->render($template, ['title' => 'something']));
    \Stillat\Dagger\Tests\StaticTestHelpers::resetCounter();
    $this->assertSame($expected, $this->render($template, ['title' => 'something']));
});

test('content is cached when using named slots within dynamic components', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 5; $i++)
<c-dynamic-component component="cache.named_slot_cached_var">
    <c-slot:header class="header class" :$title that="other stuff">
        The Header Slot Content! {{ $i }}
    </c-slot:header>
    
    Default Content! {{ $i }}
</c-dynamic-component>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
<div class="header class" title="the title" that="other stuff">
    The Header Slot Content! 0
    Var1: 1
</div>

Default Content! 0
Var2: 2

<div class="header class" title="the title" that="other stuff">
    The Header Slot Content! 1
    Var1: 1
</div>

Default Content! 1
Var2: 2

<div class="header class" title="the title" that="other stuff">
    The Header Slot Content! 2
    Var1: 1
</div>

Default Content! 2
Var2: 2

<div class="header class" title="the title" that="other stuff">
    The Header Slot Content! 3
    Var1: 1
</div>

Default Content! 3
Var2: 2

<div class="header class" title="the title" that="other stuff">
    The Header Slot Content! 4
    Var1: 1
</div>

Default Content! 4
Var2: 2

EXPECTED;

    $this->assertSame($expected, $this->render($template, ['title' => 'the title']));
    \Stillat\Dagger\Tests\StaticTestHelpers::resetCounter();
    $this->assertSame($expected, $this->render($template, ['title' => 'the title']));
});

test('cached named slots receive component variable within dynamic components', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 2; $i++)
<c-dynamic-component component="cached_named_slot_content">
    <c-slot:header>Header: {{ $component->name }}</c-slot:header>
    
    Default Slot: {{ $component->name }}
</c-dynamic-component>
@endfor

BLADE;

    $expected = <<<'EXPECTED'
Header: cached_named_slot_content
Content Default Slot: cached_named_slot_content

Header: cached_named_slot_content
Content Default Slot: cached_named_slot_content
EXPECTED;

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
});

test('multiple instances of the cached slot content are rendered within dynamic components', function () {
    $template = <<<'BLADE'
<c-dynamic-component component="cached_named_slot_content">
    <c-slot:header>Header: {{ $component->name }}</c-slot:header>
    
    Default Slot: {{ $component->name }}
</c-dynamic-component>

<c-dynamic-component component="cached_named_slot_content">
    <c-slot:header>Header: {{ $component->name }}</c-slot:header>
    
    Default Slot: {{ $component->name }}
</c-dynamic-component>

BLADE;

    $expected = <<<'EXPECTED'
Header: cached_named_slot_content
Content Default Slot: cached_named_slot_content


Header: cached_named_slot_content
Content Default Slot: cached_named_slot_content
EXPECTED;

    $this->assertSame($expected, $this->render($template));
    $this->assertSame($expected, $this->render($template));
});

test('cached instances are reused within dynamic components', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 1; $i++)
<c-dynamic-component component="cache.named_slot_cached_var">
    <c-slot:header class="header class" :$title that="other stuff">
        The Header Slot Content! {{ $i }}
    </c-slot:header>
    
    Default Content! {{ $i }}
</c-dynamic-component>
@endfor
<c-cache.named_slot_cached_var>
    <c-slot:header class="header class" :$title that="other stuff">
        The Header Slot Content! Not A Loop
    </c-slot:header>
    
    Default Content! Not A Loop
</c-cache.named_slot_cached_var>
BLADE;

    $expected = <<<'EXPECTED'
<div class="header class" title="Some Title" that="other stuff">
    The Header Slot Content! 0
    Var1: 1
</div>

Default Content! 0
Var2: 2

<div class="header class" title="Some Title" that="other stuff">
    The Header Slot Content! Not A Loop
    Var1: 1
</div>

Default Content! Not A Loop
Var2: 2

EXPECTED;

    $this->assertSame($expected, $this->render($template, ['title' => 'Some Title']));
    $this->assertSame($expected, $this->render($template, ['title' => 'Some Title']));
});
