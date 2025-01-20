<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FullyQualifiedNamespaceVisitor extends NodeVisitorAbstract
{
    private array $functionImports = [];

    private array $classImports = [];

    private array $constantImports = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse) {
            $prefix = $node instanceof Node\Stmt\GroupUse
                ? $node->prefix->toString().'\\'
                : '';

            foreach ($node->uses as $use) {
                $fullyQualifiedName = $prefix.$use->name->toString();
                $alias = $use->alias
                    ? $use->alias->toString()
                    : $use->name->getLast();

                switch ($use->type) {
                    case Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->functionImports[$alias] = $fullyQualifiedName;
                        break;

                    case Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->constantImports[$alias] = $fullyQualifiedName;
                        break;

                    default:
                        $this->classImports[$alias] = $fullyQualifiedName;
                        break;
                }
            }
        }

        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
            $this->resolveFunctionNamespace($node);
        }

        if ($node instanceof Node\Expr\New_) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();

                if (isset($this->classImports[$className])) {
                    $node->class = new Node\Name($this->classImports[$className]);
                }
            }
        }

        if ($node instanceof Node\Expr\StaticCall) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();

                if (isset($this->classImports[$className])) {
                    $node->class = new Node\Name($this->classImports[$className]);
                }
            }
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();

                if (isset($this->classImports[$className])) {
                    $node->class = new Node\Name($this->classImports[$className]);
                }
            }
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            $this->resolveConstNamespace($node);
        }
    }

    protected function resolveConstNamespace(Node\Expr\ConstFetch $constFetch)
    {
        $functionName = $constFetch->name->toString();

        if (isset($this->constantImports[$functionName])) {
            $constFetch->name = new Node\Name($this->constantImports[$functionName]);
        } elseif (isset($this->classImports[$functionName])) {
            $constFetch->name = new Node\Name($this->classImports[$functionName]);
        }
    }

    protected function resolveFunctionNamespace(Node\Expr\FuncCall $function)
    {
        $functionName = $function->name->toString();

        if (isset($this->functionImports[$functionName])) {
            $function->name = new Node\Name($this->functionImports[$functionName]);
        } elseif (isset($this->classImports[$functionName])) {
            $function->name = new Node\Name($this->classImports[$functionName]);
        }
    }
}
