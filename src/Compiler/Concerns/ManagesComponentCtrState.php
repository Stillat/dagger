<?php

namespace Stillat\Dagger\Compiler\Concerns;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
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

    protected function containsOtherComponents(string $template): bool
    {
        preg_match_all(
            '/<([a-zA-Z0-9_]+)([-:])[a-zA-Z0-9_\-:]*(?:\s[^>]*)?>/',
            $template,
            $matches,
            PREG_SET_ORDER
        );

        if (! $matches) {
            return false;
        }

        foreach ($matches as $match) {
            if (! in_array(mb_strtolower($match[1]), $this->componentNamespaces)) {
                return true;
            }
        }

        return false;
    }

    protected function checkForCtrEligibility(string $originalTemplate, string $compiledTemplate): void
    {
        if (! $this->activeComponent->options->allowCtr) {
            $this->activeComponent->isCtrEligible = false;

            return;
        }

        if ($this->containsOtherComponents($originalTemplate)) {
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
