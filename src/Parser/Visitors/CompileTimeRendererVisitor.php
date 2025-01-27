<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Stillat\Dagger\Compiler\ComponentState;

class CompileTimeRendererVisitor implements NodeVisitor
{
    protected ?ComponentState $componentState = null;

    protected bool $isCtrEligible = true;

    protected array $nonCtrMethodNames = [
        'Stillat\Dagger\component',
        'Stillat\Dagger\render',
        'Stillat\Dagger\_parent',
        'Stillat\Dagger\current',
    ];

    protected array $unsafeFunctionCalls = [];

    protected array $unsafeVariableNames = [];

    public function reset(): self
    {
        $this->isCtrEligible = true;

        return $this;
    }

    public function setUnsafeVariableNames(array $unsafeVariableNames): static
    {
        $this->unsafeVariableNames = $unsafeVariableNames;

        return $this;
    }

    public function setUnsafeFunctionCalls(array $unsafeFunctionCalls): static
    {
        $this->unsafeFunctionCalls = $unsafeFunctionCalls;

        return $this;
    }

    public function setComponentState(ComponentState $componentState): self
    {
        $this->componentState = $componentState;

        return $this;
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

            if (in_array($name, $this->unsafeFunctionCalls)) {
                $this->isCtrEligible = false;

                return;
            }

            if (in_array($name, $this->nonCtrMethodNames)) {
                $this->isCtrEligible = false;
            }
        } elseif ($node instanceof Node\Expr\Variable) {
            $prefixed = '$'.$node->name;

            if (in_array($node->name, $this->unsafeVariableNames) || in_array($prefixed, $this->unsafeVariableNames)) {
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
