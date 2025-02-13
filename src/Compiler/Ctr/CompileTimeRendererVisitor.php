<?php

namespace Stillat\Dagger\Compiler\Ctr;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Compiler\DisableOptimization;
use Stillat\Dagger\Compiler\EnableOptimization;

class CompileTimeRendererVisitor implements NodeVisitor
{
    protected ?ComponentState $componentState = null;

    protected bool $isCtrEligible = true;

    protected array $allowedFrameworkClasses = [
        \Illuminate\Support\Str::class,
        \Illuminate\Support\Arr::class,
        \Illuminate\Support\Facades\Cache::class,
        \Illuminate\Support\Facades\Config::class,
        \Illuminate\Support\Facades\Crypt::class,
        \Illuminate\Support\Facades\Hash::class,
        \Illuminate\Support\Facades\Http::class,
        \Illuminate\Support\Js::class,
        \Illuminate\Support\Facades\URL::class,
        \Illuminate\Support\Facades\Validator::class,
    ];

    protected array $disabledExpressions = [
        Node\Expr\Eval_::class,
        Node\Expr\Include_::class,
        Node\Expr\Exit_::class,
        Node\Expr\ShellExec::class,
    ];

    protected array $nonCtrMethodNames = [
        'Stillat\Dagger\component',
        'Stillat\Dagger\render',
        'Stillat\Dagger\_parent',
        'Stillat\Dagger\current',
    ];

    protected array $restrictedComponentProperties = [
        'depth',
        'parent',
    ];

    protected array $unsafeFunctionCalls = [];

    protected array $unsafeVariableNames = [];

    protected array $appAliases = [];

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

    public function setAppAliases(array $aliases): static
    {
        $this->appAliases = $aliases;

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

            if (in_array($name, $this->restrictedComponentProperties)) {
                $this->isCtrEligible = false;

                return;
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
        } elseif ($node instanceof Node\Expr\Variable && $this->isUnsafeVariable($node->name)) {
            $this->isCtrEligible = false;

            return;
        } elseif ($node instanceof Node\Expr\StaticCall) {
            $name = $this->getStaticCallName($node);

            if (! $name) {
                $this->isCtrEligible = false;

                return;
            }

            if (! class_exists($name)) {
                // The class may be available at runtime; disable CTR.
                $this->isCtrEligible = false;

                return;
            }

            if (in_array($name, $this->allowedFrameworkClasses)) {
                return;
            }

            $methodName = $node->name->toString();
            $reflectionClass = new ReflectionClass($name);

            if (! $reflectionClass->hasMethod($methodName)) {
                $this->isCtrEligible = false;

                return;
            }

            $this->isCtrEligible = $this->isCtrAllowed($reflectionClass, $reflectionClass->getMethod($methodName));
        } elseif (in_array(get_class($node), $this->disabledExpressions)) {
            $this->isCtrEligible = false;
        }
    }

    protected function isCtrAllowed(ReflectionClass $class, ReflectionMethod $method): bool
    {
        /** @var \ReflectionAttribute $methodCtrAttribute */
        if ($methodCtrAttribute = $this->getCtrAttribute($method)) {
            return $methodCtrAttribute->getName() == EnableOptimization::class;
        }

        if ($this->getCtrAttribute($class)?->getName() == EnableOptimization::class) {
            return true;
        }

        return false;
    }

    protected function getCtrAttribute($reflectedObject): ?ReflectionAttribute
    {
        $ctrAllowed = $reflectedObject->getAttributes(EnableOptimization::class);

        if (! empty($ctrAllowed)) {
            return $ctrAllowed[0];
        }

        $ctrDisabled = $reflectedObject->getAttributes(DisableOptimization::class);

        if (! empty($ctrDisabled)) {
            return $ctrDisabled[0];
        }

        return null;
    }

    protected function isUnsafeVariable(string $name): bool
    {
        $prefixed = '$'.$name;

        return in_array($name, $this->unsafeVariableNames) || in_array($prefixed, $this->unsafeVariableNames);
    }

    protected function getStaticCallName(Node\Expr\StaticCall $call): string
    {
        $name = '';

        if ($call->class instanceof Node\Name\FullyQualified) {
            $name = $call->class->toString();
        } elseif ($call->class instanceof Node\Name) {
            $name = $call->class->toString();

            $name = $this->appAliases[$name] ?? $name;
        }

        return $name;
    }

    public function isEligibleForCtr(): bool
    {
        return $this->isCtrEligible;
    }

    public function beforeTraverse(array $nodes) {}

    public function leaveNode(Node $node) {}

    public function afterTraverse(array $nodes) {}
}
