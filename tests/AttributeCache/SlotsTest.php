<?php

use Illuminate\Support\Str;

test('it replaces basic slot content', function () {
    $template = <<<'BLADE'
<c-cache.slots>Basic!</c-cache.slots>
BLADE;

    $this->assertSame(
        'Start Basic! End',
        Str::squish($this->render($template))
    );
});

test('it does not insert replacement strings', function () {
    $template = <<<'BLADE'
<c-cache.slots />
BLADE;

    $this->assertSame(
        'Start End',
        Str::squish($this->render($template))
    );
});

test('cached tag pairs and non tag pairs can be used', function () {
    $template = <<<'BLADE'
A <c-cache.slots>Slot Content One</c-cache.slots>
B <c-cache.slots />
C <c-cache.slots>Slot Content Two</c-cache.slots>
BLADE;

    $this->assertSame(
        'A Start Slot Content One EndB Start EndC Start Slot Content Two End',
        Str::squish($this->render($template))
    );
});

test('cached slot contents receive variables from outer scope', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 10; $i++)
<c-cache.slots>Slot: {{ $i }}</c-cache.slots>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Start
Slot: 0
End
Start
Slot: 1
End
Start
Slot: 2
End
Start
Slot: 3
End
Start
Slot: 4
End
Start
Slot: 5
End
Start
Slot: 6
End
Start
Slot: 7
End
Start
Slot: 8
End
Start
Slot: 9
End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('uncached components evaluate their contents', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 10; $i++)
<c-cache.slot_uncached_var_content>Slot: {{ $i }}</c-cache.slot_uncached_var_content>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Var: 1
Start
Slot: 0
End
Var: 2
Start
Slot: 1
End
Var: 3
Start
Slot: 2
End
Var: 4
Start
Slot: 3
End
Var: 5
Start
Slot: 4
End
Var: 6
Start
Slot: 5
End
Var: 7
Start
Slot: 6
End
Var: 8
Start
Slot: 7
End
Var: 9
Start
Slot: 8
End
Var: 10
Start
Slot: 9
End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('cached components do not re-evaluate their contents', function () {
    $template = <<<'BLADE'
@for ($i = 0; $i < 10; $i++)
<c-cache.slot_cached_var_content>Slot: {{ $i }}</c-cache.slot_cached_var_content>
@endfor
BLADE;

    $expected = <<<'EXPECTED'
Var: 1
Start
Slot: 0
EndVar: 1
Start
Slot: 1
EndVar: 1
Start
Slot: 2
EndVar: 1
Start
Slot: 3
EndVar: 1
Start
Slot: 4
EndVar: 1
Start
Slot: 5
EndVar: 1
Start
Slot: 6
EndVar: 1
Start
Slot: 7
EndVar: 1
Start
Slot: 8
EndVar: 1
Start
Slot: 9
End
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('cached component slots receive component variable', function () {
    $template = <<<'BLADE'
<c-cached_slot_content>One {{ $component->name }}</c-cached_slot_content>
<c-cached_slot_content>Two {{ $component->name }}</c-cached_slot_content>
BLADE;

    $expected = <<<'EXPECTED'
Content One cached_slot_content
Content Two cached_slot_content
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
