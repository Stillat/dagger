<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;

class ComponentModelVisitor implements NodeVisitor
{
    protected $componentChain = null;

    protected $componentCall = null;

    public function beforeTraverse(array $nodes) {}

    protected function isComponentCall(Node\Expr\FuncCall $call): bool
    {
        $funcName = $call->name->toString();

        return $funcName == 'component' || $funcName == 'Stillat\Dagger\component';
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\MethodCall) {
            $root = $this->getChainRoot($node);

            if ($root instanceof Node\Expr\FuncCall &&
                $root->name instanceof Node\Name &&
                $this->isComponentCall($root)
            ) {
                $this->componentCall = $root;
                $this->componentChain = $this->getExpressionRoot($root);
            }
        }
    }

    public function leaveNode(Node $node) {}

    public function afterTraverse(array $nodes) {}

    private function getExpressionRoot(Node $node)
    {
        $current = $node;
        while ($current->getAttribute('parent') !== null) {
            $current = $current->getAttribute('parent');
            if ($current instanceof Node\Stmt\Expression) {
                return $current;
            }
        }

        return null;
    }

    private function getChainRoot(Node\Expr\MethodCall $node)
    {
        while ($node instanceof Node\Expr\MethodCall) {
            $node = $node->var;
        }

        return $node;
    }

    public function getComponentCall()
    {
        return $this->componentCall;
    }

    public function getComponentChain()
    {
        return $this->componentChain;
    }
}
