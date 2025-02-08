<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FindFunctionsVisitor extends NodeVisitorAbstract
{
    protected array $functionNames = [];

    public function enterNode(Node $node)
    {
        if (! $node instanceof Node\Stmt\Function_) {
            return;
        }

        $this->functionNames[] = $node->name->toString();
    }

    public function getFunctionNames(): array
    {
        return $this->functionNames;
    }
}
