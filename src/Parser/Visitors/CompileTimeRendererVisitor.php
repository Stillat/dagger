<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Stillat\Dagger\Compiler\ComponentState;

class CompileTimeRendererVisitor implements NodeVisitor
{
    protected ComponentState $componentState;

    protected bool $isCtrEligible = true;

    protected array $nonCtrMethodNames = [
        'Stillat\Dagger\component',
        'Stillat\Dagger\render',
        'Stillat\Dagger\_parent',
        'Stillat\Dagger\current',
    ];

    public function __construct(ComponentState $componentState)
    {
        $this->componentState = $componentState;
    }

    protected function isComponentVar($node): bool
    {
        if (! $node instanceof Node\Expr\Variable) {
            return false;
        }

        return $node->name == $this->componentState->getVariableName();
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\PropertyFetch) {
            if (! $this->isComponentVar($node->var)) {
                return;
            }

            $name = $node->name->toString();

            if ($name === 'depth') {
                $this->isCtrEligible = false;
            } elseif ($name === 'parent') {
                $this->isCtrEligible = false;
            }
        } elseif ($node instanceof Node\Expr\MethodCall) {
            if (! $this->isComponentVar($node->var)) {
                return;
            }

            if ($node->name->toString() === 'parent') {
                $this->isCtrEligible = false;
            }
        } elseif ($node instanceof Node\Expr\FuncCall) {
            if (! $node->name instanceof Node\Name) {
                return;
            }

            $name = $node->name->toString();

            if (in_array($name, $this->nonCtrMethodNames)) {
                $this->isCtrEligible = false;
            }
        }
    }

    public function isEligibleForCtr(): bool
    {
        return $this->isCtrEligible;
    }

    public function beforeTraverse(array $nodes) {}

    public function leaveNode(Node $node) {}

    public function afterTraverse(array $nodes) {}
}
