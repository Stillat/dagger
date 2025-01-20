<?php

namespace Stillat\Dagger\Compiler\ComponentStages;

use Closure;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;

class RemoveUseStatements
{
    public function handle($ast, Closure $next)
    {
        return $next(collect($ast)->filter(fn ($node) => ! $this->isUse($node))->values()->all());
    }

    protected function isUse($node): bool
    {
        return $node instanceof Use_ || $node instanceof GroupUse;
    }
}
