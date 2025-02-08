<?php

namespace Stillat\Dagger\Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Stillat\Dagger\Support\Utils;

class RenameFunctionVisitor extends NodeVisitorAbstract
{
    protected array $functionNames = [];

    public function __construct(array $functionNames)
    {
        $this->functionNames = $this->buildFunctionNameMap($functionNames);
    }

    protected function buildFunctionNameMap(array $functionNames): array
    {
        return collect($functionNames)
            ->mapWithKeys(function ($name) {
                return [$name => $name.'_'.Utils::makeRandomString()];
            })
            ->all();
    }

    public function enterNode(Node $node)
    {
        if (! $node instanceof Node\Expr\FuncCall || ! $node->name instanceof Node\Name) {
            return;
        }

        $functionName = $node->name->toString();

        if (! isset($this->functionNames[$functionName])) {
            return;
        }

        $node->name = new Node\Name($this->functionNames[$functionName]);
    }

    public function leaveNode(Node $node)
    {
        if (! $node instanceof Node\Stmt\Function_) {
            return null;
        }

        $functionName = $node->name->toString();

        if (! isset($this->functionNames[$functionName])) {
            return null;
        }

        $newFunctionName = $this->functionNames[$functionName];

        $node->name = new Node\Identifier($newFunctionName);

        return new Node\Stmt\If_(
            new Node\Expr\BooleanNot(
                new Node\Expr\FuncCall(
                    new Node\Name('function_exists'),
                    [new Node\Arg(new Node\Scalar\String_($newFunctionName))]
                )
            ),
            [
                'stmts' => [$node],
            ]
        );
    }
}
