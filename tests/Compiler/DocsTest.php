<?php

use Stillat\Dagger\Tests\CompilerTestCase;

uses(CompilerTestCase::class);

test('alert example', function () {
    $template = <<<'BLADE'
<c-docs.alert type="error" :message="$message" class="mb-4"/>
BLADE;

    $expected = <<<'EXPECTED'
<div class="alert alert-error mb-4">
    The Message
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template, ['message' => 'The Message'])
    );
});

test('functional alert example', function () {
    $template = <<<'BLADE'
<c-docs.alert_functional type="error" :message="$message" class="mb-4"/>
BLADE;

    $expected = <<<'EXPECTED'
<div class="alert alert-error mb-4">
    The Message
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template, ['message' => 'The Message'])
    );
});

test('php removed compiler notes example', function () {
    $expected = <<<'EXPECTED'
<div class="alert alert-info">
    the value
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-docs.php_removed />')
    );

    $expected = <<<'EXPECTED'
<div class="alert alert-info">
    the value
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-docs.php_after_component_function />')
    );
});

test('renaming the component variable example', function () {
    $template = <<<'BLADE'
<c-docs.renamed_component type="error" :message="$message" class="mb-4"/>
BLADE;

    $expected = <<<'EXPECTED'
<div class="alert alert-error mb-4">
    The Message
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template, ['message' => 'The Message'])
    );
});

test('renamed component variable keeps original name in slots', function () {
    $template = <<<'BLADE'
<c-docs.renamed_component_var_slot>
    The Slot Contents {{ $component->type }}
</c-docs.renamed_component_var_slot>
BLADE;

    $expected = <<<'EXPECTED'
<div class="alert alert-info">
    The Slot Contents info
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('aware directive in menu example', function () {
    $template = <<<'BLADE'
<c-docs.menu_aware_directive color="purple">
    <c-docs.menu_aware_directive.item>...</c-docs.menu_aware_directive.item>
    <c-docs.menu_aware_directive.item>...</c-docs.menu_aware_directive.item>
</c-docs.menu_aware_directive>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/views/components/menu/index.blade.php -->



<ul class="bg-purple-200">
    <!-- /resources/views/components/menu/item.blade.php -->



<li class="text-purple-800">
    ...
</li>    <!-- /resources/views/components/menu/item.blade.php -->



<li class="text-purple-800">
    ...
</li>
</ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('aware function builder in menu example', function () {
    $template = <<<'BLADE'
<c-docs.menu_aware_builder color="purple">
    <c-docs.menu_aware_builder.item>...</c-docs.menu_aware_builder.item>
    <c-docs.menu_aware_builder.item>...</c-docs.menu_aware_builder.item>
</c-docs.menu_aware_builder>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/views/components/menu/index.blade.php -->

<ul class="bg-purple-200">
    <!-- /resources/views/components/menu/item.blade.php -->

<li class="text-purple-800">
    ...
</li>    <!-- /resources/views/components/menu/item.blade.php -->

<li class="text-purple-800">
    ...
</li>
</ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('accessing arbitrary component parent menu example', function () {
    $template = <<<'BLADE'
<c-docs.menu_arbitrary color="purple">
    <c-docs.menu_arbitrary.item>...</c-docs.menu_arbitrary.item>
    <c-docs.menu_arbitrary.item>...</c-docs.menu_arbitrary.item>
</c-docs.menu_arbitrary>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/views/components/menu/index.blade.php -->

<ul class="bg-purple-200">
    <!-- /resources/views/components/menu/item.blade.php -->

<li class="text-purple-800">
    ...
</li>    <!-- /resources/views/components/menu/item.blade.php -->

<li class="text-purple-800">
    ...
</li>
</ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('button validation example triggers exception', function () {
    $this->expectExceptionMessage('The title property is required.');
    $this->render('<c-docs.button_validate />');
});

test('button validation does not trigger exception when passed a title', function () {
    $this->assertSame(
        'The Title',
        $this->render('<c-docs.button_validate title="The Title" />')
    );
});

test('button shorthand validation example triggers exception', function () {
    $this->expectExceptionMessage('The title property is required.');
    $this->render('<c-docs.shorthand_button_validate />');
});

test('button shorthand validation does not trigger exception when passed a title', function () {
    $this->assertSame(
        'The Title',
        $this->render('<c-docs.shorthand_button_validate title="The Title" />')
    );
});

test('named/scoped slot example', function () {
    $template = <<<'BLADE'
<c-docs.namedslot>
    <c-slot:header class="header classes here">
        Header Content
    </c-slot:header>
    
    <c-slot:footer class="header classes here">
        Footer Content
    </c-slot:footer>
    
    Default Slot Content
</c-docs.namedslot>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/panel.blade.php -->
<div class="header classes here">
    Header Content
</div>

Default Slot Content

<div class="header classes here">
    Footer Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('attribute forwarding example', function () {
    $template = <<<'BLADE'
<c-docs.forwarding.toolbar />
BLADE;

    $expected = <<<'EXPECTED'
<div>
    <button >The Save Button</button>    <button >The Cancel Button</button></div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-docs.forwarding.toolbar
    #saveButton:text="A New Save Button"
    #saveButton:class="mr-0"
    #cancelButton:text="A New Cancel Button"
    #cancelButton:style="display:none"
/>
BLADE;

    $expected = <<<'BLADE'
<div>
    <button class="mr-0">A New Save Button</button>    <button style="display:none">A New Cancel Button</button></div>
BLADE;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('mixin example', function () {
    $template = <<<'BLADE'
<c-docs.mixin_example />
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/mixin.blade.php -->

<div class="bg-indigo-500">
    ...
</div>

EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-docs.mixin_example background="bg-blue-500" />
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/mixin.blade.php -->

<div class="bg-blue-500">
    ...
</div>

EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('calling methods example', function () {
    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/profile.blade.php -->

<div>
    Hello, John.
</div>

EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-docs.mixin_method name="John" />')
    );

    $this->assertSame(
        $expected,
        $this->render('<c-docs.mixin_methods_component_proxy name="John" />')
    );
});

test('mixin receiving component example', function () {
    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/component.blade.php -->

<div>
    DOCS.MIXIN_WITH_COMPONENT
</div>

EXPECTED;

    $this->assertSame(
        $expected,
        $this->render('<c-docs.mixin_with_component />')
    );
});

test('output trimming example', function () {
    $this->assertSame(
        'John',
        $this->render('<c-docs.output_trimming name="John" />')
    );
});

test('stencil example', function () {
    $template = <<<'BLADE'
<c-docs.list :items="['Alice', 'Bob', 'Charlie']" />
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/list/index.blade.php -->


<ul>
            <li>Alice</li>
            <li>Bob</li>
            <li>Charlie</li>
    </ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-docs.list :items="['Alice', 'Bob', 'Charlie']">
    <c-stencil:list_item>
        <li class="mt-2">{{ Str::upper($item) }}</li>
    </c-stencil:list_item>
</c-docs.list>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/list/index.blade.php -->


<ul>
            <li class="mt-2">ALICE</li>
            <li class="mt-2">BOB</li>
            <li class="mt-2">CHARLIE</li>
    </ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );

    $template = <<<'BLADE'
<c-docs.list :items="['Alice', 'Bob', 'Charlie']">
    <c-stencil:list_item>
        @if ($item === 'Alice')
            <li data-something="special-for-alice">{{ $item }}</li>
        @else
            <c-stencil:list_item.default />
        @endif
    </c-stencil:list_item>
</c-docs.list>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/list/index.blade.php -->


<ul>
                        <li data-something="special-for-alice">Alice</li>
                                <li>Bob</li>
                                <li>Charlie</li>
            </ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('nested attribute forwarding example', function () {
    $template = <<<'BLADE'
<c-docs.forwarding.nested_one
    #nestedTwo.nestedThree:title="The Nested Title"
/>
BLADE;

    $this->assertSame(
        'The Nested Title',
        $this->render($template)
    );
});

test('attribute forwarding variable binding example', function () {
    $template = <<<'BLADE'
<c-docs.forwarding.nested_one
    :#nestedTwo.nestedThree:title="$title"
/>
BLADE;

    $this->assertSame(
        'A variable title',
        $this->render($template, ['title' => 'A variable title'])
    );
});

test('slot forwarding example', function () {
    $template = <<<'BLADE'
<c-docs.slot_forwarding.root_two>
    <c-slot:componentOne.header class="header classes here">
        Nested Header Content
    </c-slot:componentOne.header>
    
    <c-slot:componentOne.footer class="footer classes here">
        Nested Footer Content
    </c-slot:componentOne.footer>
    <c-slot:componentOne.default>
        Nested Default Content
    </c-slot:componentOne.default>
</c-docs.slot_forwarding.root_two>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/panel.blade.php -->
<div class="header classes here">
    Nested Header Content
</div>

Nested Default Content

<div class="footer classes here">
    Nested Footer Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});

test('nested slot forwarding example', function () {
    $template = <<<'BLADE'
<c-docs.slot_forwarding.root_one>
    <c-slot:componentOne.componentTwo.header class="header classes here">
        Nested Header Content
    </c-slot:componentOne.componentTwo.header>
    
    <c-slot:componentOne.componentTwo.footer class="footer classes here">
        Nested Footer Content
    </c-slot:componentOne.componentTwo.footer>
    <c-slot:componentOne.componentTwo.default>
        Nested Default Content
    </c-slot:componentOne.componentTwo.default>
</c-docs.slot_forwarding.root_one>
BLADE;

    $expected = <<<'EXPECTED'
<!-- /resources/dagger/views/panel.blade.php -->
<div class="header classes here">
    Nested Header Content
</div>

Nested Default Content

<div class="footer classes here">
    Nested Footer Content
</div>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template)
    );
});
