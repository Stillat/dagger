<?php

namespace Stillat\Dagger\Compiler\Concerns;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use Stillat\Dagger\Compiler\Renderer;
use Stillat\Dagger\Ctr\CompileTimeRendererVisitor;
use Stillat\Dagger\Parser\PhpParser;

trait ManagesComponentCtrState
{
    protected ?CompileTimeRendererVisitor $ctrVisitor = null;

    public function getCtrVisitor(): CompileTimeRendererVisitor
    {
        if ($this->ctrVisitor) {
            return $this->ctrVisitor;
        }

        return $this->ctrVisitor = new CompileTimeRendererVisitor;
    }

    protected function checkForCtrEligibility(string $originalTemplate, string $compiledTemplate): void
    {
        if (! $this->activeComponent->options->allowCtr) {
            $this->activeComponent->isCtrEligible = false;

            return;
        }

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

        $ctrVisitor = $this->getCtrVisitor()
            ->reset()
            ->setComponentState($this->activeComponent);

        $traverser->addVisitor($ctrVisitor);
        $traverser->traverse($ast);

        $this->activeComponent->isCtrEligible = $ctrVisitor->isEligibleForCtr();
    }
}
