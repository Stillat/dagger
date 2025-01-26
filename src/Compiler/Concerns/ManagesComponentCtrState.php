<?php

namespace Stillat\Dagger\Compiler\Concerns;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use Stillat\Dagger\Compiler\Renderer;
use Stillat\Dagger\Parser\PhpParser;
use Stillat\Dagger\Parser\Visitors\CompileTimeRendererVisitor;

trait ManagesComponentCtrState
{
    protected function checkForCtrEligibility(string $originalTemplate, string $compiledTemplate): void
    {
        if (Renderer::containsOtherComponents($originalTemplate)) {
            $this->activeComponent->isCtrEligible = false;

            return;
        }

        $traverser = new NodeTraverser;

        $ast = PhpParser::makeParser()->parse($compiledTemplate);
        $parentingVisitor = new ParentConnectingVisitor;
        $traverser->addVisitor($parentingVisitor);
        $traverser->traverse($ast);

        $traverser->removeVisitor($parentingVisitor);

        $ctrVisitor = new CompileTimeRendererVisitor($this->activeComponent);

        $traverser->addVisitor($ctrVisitor);
        $traverser->traverse($ast);

        $this->activeComponent->isCtrEligible = $ctrVisitor->isEligibleForCtr();
    }
}
