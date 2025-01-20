<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\PrettyPrinter;
use PhpParser\PrettyPrinter\Standard;

class StringifyMethodArgsVisitor implements NodeVisitor
{
    protected PrettyPrinter $printer;

    protected array $methodNames = [];

    public function __construct(array $methodNames)
    {
        $this->printer = new Standard;
        $this->methodNames = $methodNames;
    }

    public function beforeTraverse(array $nodes) {}

    public function enterNode(Node $node) {}

    public function leaveNode(Node $node)
    {
        if (! $node instanceof Node\Expr\MethodCall) {
            return;
        }

        if (! $node->name instanceof Node\Identifier || ! in_array($node->name->toString(), $this->methodNames)) {
            return;
        }

        if (empty($node->args)) {
            return;
        }

        $args = [];

        foreach ($node->args as $arg) {
            $args[] = new Node\Scalar\String_($this->printer->prettyPrint([$arg]));
        }

        $node->args = $args;
    }

    public function afterTraverse(array $nodes) {}
}
