<?php

namespace Stillat\Dagger\Compiler\ComponentStages;

use Closure;
use PhpParser\NodeTraverser;
use Stillat\Dagger\Parser\Visitors\FindFunctionsVisitor;
use Stillat\Dagger\Parser\Visitors\RenameFunctionVisitor;

class RewriteFunctions extends AbstractStage
{
    public function handle($ast, Closure $next)
    {
        $traverser = new NodeTraverser;

        $finder = new FindFunctionsVisitor;
        $traverser->addVisitor($finder);
        $traverser->traverse($ast);

        $traverser->removeVisitor($finder);

        $modifyFunctionsVisitor = new RenameFunctionVisitor($finder->getFunctionNames());
        $traverser->addVisitor($modifyFunctionsVisitor);

        return $next($traverser->traverse($ast));
    }
}
