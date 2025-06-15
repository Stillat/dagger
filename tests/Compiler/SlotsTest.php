<?php

use Illuminate\Support\Str;

test('default slot scope behavior is equivalent to blade components', function () {
    $bladeComponent = <<<'BLADE'
<?php $theVar = 15; ?>
<x-componentslot>
Slot Content: {{ $title }}
<?php $theVar += 10; ?>
</x-componentslot>
 {{ $theVar }}
BLADE;

    $this->assertSame(
        'No Title Slot Content: The Title 25',
        Str::squish($this->render($bladeComponent, ['title' => 'The Title']))
    );

    $component = <<<'BLADE'
<?php $theVar = 15; ?>
<c-componentslot>
Slot Content: {{ $title }}
<?php $theVar += 10; ?>
</c-componentslot>
 {{ $theVar }}
BLADE;

    $this->assertSame(
        'No Title Slot Content: The Title 25',
        Str::squish($this->render($component, ['title' => 'The Title']))
    );
});

test('it compiles named slots', function () {
    $component = <<<'BLADE'
<c-namedslots>
    <c-slot:header>
        The header content.    
    </c-slot:header>
</c-namedslots>
BLADE;

    $this->assertSame(
        'Before. The header content. After.',
        $this->render($component)
    );
});

test('it compiles multiple named slots', function () {
    $template = <<<'PHP'
<c-multiplenamedslots class="default slot attributes">
    <c-slot:header class="header slot attributes">
        Header Content
    </c-slot:header>
    
    Default Content
    
    <c-slot:footer class="footer slot attributes">
        Footer Content
    </c-slot:footer>
</c-multiplenamedslots>
PHP;

    $expected = <<<'EXPECTED'
<div id="header" class="header slot attributes">
    Header Content
</div>

<div class="default slot attributes">
    Default Content
</div>

<div id="footer" class="footer slot attributes">
    Footer Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('it compiles named slot attributes', function () {
    $component = <<<'BLADE'
<c-namedslots>
    <c-slot:header class="one two three">
        The header content.    
    </c-slot:header>
</c-namedslots>
BLADE;

    $this->assertSame(
        'Before. class="one two three"The header content. After.',
        $this->render($component)
    );
});

test('conditional slots are compiled', function () {
    $this->assertSame('No', trim($this->render('<c-conditional_slots></c-conditional_slots>')));
    $this->assertSame('No', trim($this->render('<c-conditional_slots />')));
    $this->assertSame('Yes', trim($this->render('<c-conditional_slots><c-slot:footer>Footer!</c-slot:footer></c-conditional_slots>')));
});

test('components are compiled inside named slots', function () {
    $template = <<<'BLADE'
<c-namedslots>
    <c-slot:header>
        <c-static />
    </c-slot:header>
</c-namedslots>
BLADE;

    $this->assertSame(
        'Before. Some Static Content {{ title }} After.',
        $this->render($template)
    );
});

test('nested named slots are compiled', function () {
    $template = <<<'BLADE'
<c-multiplenamedslots class="default slot attributes">
    <c-slot:header class="header slot attributes">
        Header Content
    </c-slot:header>
    
    <!-- Default One -->
    <c-multiplenamedslots class="default slot attributes two" another>
        <c-slot:header class="header slot attributes two">
            Header Content Two
        </c-slot:header>
        
        <!-- Default Two -->
        <c-multiplenamedslots class="default slot attributes two" another three>
            <c-slot:header class="header slot attributes three">
                Header Content Three
            </c-slot:header>
            
            <!-- Default Three -->
            <x-alert />
            <c-static />
            {{ $title }}
            <!-- /Default Three -->
            
            <c-slot:footer class="footer slot attributes three" three>
                Footer Content Three
            </c-slot:footer>
        </c-multiplenamedslots>
        <!-- /Default Two -->
        
        <c-slot:footer class="footer slot attributes two">
            Footer Content Two
        </c-slot:footer>
    </c-multiplenamedslots>
    <!-- /Default One -->
    
    <c-slot:footer class="footer slot attributes">
        Footer Content
    </c-slot:footer>
</c-multiplenamedslots>
BLADE;

    $expected = <<<'EXPECTED'
<div id="header" class="header slot attributes">
    Header Content
</div>

<div class="default slot attributes">
    <!-- Default One -->
    <div id="header" class="header slot attributes two">
    Header Content Two
</div>

<div class="default slot attributes two" another="another">
    <!-- Default Two -->
        <div id="header" class="header slot attributes three">
    Header Content Three
</div>

<div class="default slot attributes two" another="another" three="three">
    <!-- Default Three -->
            Blade component test.             Some Static Content {{ title }}
            The Title
            <!-- /Default Three -->
</div>

<div id="footer" class="footer slot attributes three" three="three">
    Footer Content Three
</div>        <!-- /Default Two -->
</div>

<div id="footer" class="footer slot attributes two">
    Footer Content Two
</div>    <!-- /Default One -->
</div>

<div id="footer" class="footer slot attributes">
    Footer Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template, ['title' => 'The Title'])
    );
});

test('slots receive component variable', function () {
    $template = <<<'BLADE'
<c-slot_content>Test {{ $component->name }} </c-slot_content>
BLADE;

    $this->assertSame(
        'Content Test slot_content',
        $this->render($template)
    );
});

test('named slots receive component variable', function () {
    $template = <<<'BLADE'
<c-named_slot_content>
    <c-slot:header>Header: {{ $component->name }}</c-slot:header>
    
    Default Slot: {{ $component->name }}
</c-named_slot_content>
BLADE;

    $expected = <<<'EXPECTED'
Header: named_slot_content
Content Default Slot: named_slot_content
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('default slot value is injected', function () {
    $this->assertSame('<div></div>', $this->render('<c-empty_slot />'));
});
