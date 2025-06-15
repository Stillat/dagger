<?php

use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Exceptions\InvalidArgumentException;
use Stillat\Dagger\Facades\Compiler;

test('custom compiler callbacks are invoked', function () {
    $template = <<<'BLADE'
<c-callback />
BLADE;

    Compiler::compileComponent('c:callback', function () {
        return 'Hello, world!';
    });

    $this->assertSame('Hello, world!', $this->render($template));
});

test('custom compiler callbacks receive component node instance', function () {
    $template = <<<'BLADE'
<c-callback />
BLADE;

    Compiler::compileComponent('c:callback', function (ComponentNode $node) {
        return 'Component: '.$node->tagName;
    });

    $this->assertSame('Component: callback', $this->render($template));
});

test('custom compiler callbacks can use wildcard patterns', function () {
    $template = <<<'BLADE'
<c-callback:name />
BLADE;

    Compiler::compileComponent('c:callback:*', function (ComponentNode $node) {
        return 'Component: '.$node->tagName;
    });

    $this->assertSame('Component: callback:name', $this->render($template));
});

test('custom compiler component callback receives compiled inner content', function () {
    $template = <<<'BLADE'
<c-callback>
    Content: <c-basic title="The Basic Component" />
</c-callback>
BLADE;

    Compiler::compileComponent('c:callback', function (ComponentNode $node, string $innerContent) {
        return trim($innerContent);
    });

    $this->assertSame('Content: The Basic Component', $this->render($template));
});

test('registering a callback for a non-existent component prefix/namespace throws exception', function () {
    $this->expectException(InvalidArgumentException::class);

    Compiler::compileComponent('thing:callback', fn () => null);
});

test('registering a callback for an invalid component name throws exception', function () {
    $this->expectException(InvalidArgumentException::class);

    Compiler::compileComponent('c:', fn () => null);
});
