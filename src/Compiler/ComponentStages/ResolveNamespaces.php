<?php

namespace Stillat\Dagger\Compiler\ComponentStages;

use Closure;
use Stillat\Dagger\Parser\Visitors\FullyQualifiedNamespaceVisitor;

class ResolveNamespaces extends AbstractStage
{
    public function handle($ast, Closure $next)
    {
        $this->traverser->addVisitor(new FullyQualifiedNamespaceVisitor);

        return $next($this->traverser->traverse($ast));
    }
}
