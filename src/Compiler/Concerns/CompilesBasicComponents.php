<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Stillat\BladeParser\Nodes\Components\ComponentNode;

trait CompilesBasicComponents
{
    protected function compileBasicComponent(ComponentNode $node): string
    {
        if ($node->isClosedBy != null && ! $node->isSelfClosing) {
            return $node->outerDocumentContent;
        }

        return $node->content;
    }

    /**
     * Splits the component's tag name into a prefix and suffix based on either a colon (:) or a dot (.).
     * *
     * * If no colon or dot is found, the prefix will be null and the entire tag name
     * * is returned as the suffix. Otherwise, the first encountered delimiter is
     * * used to separate the prefix from the suffix. Used for slots/stencils.
     */
    protected function getPrefixedComponentName(ComponentNode $component): array
    {
        $tagName = $component->tagName;
        $colonPos = mb_strpos($tagName, ':');
        $dotPos = mb_strpos($tagName, '.');

        if ($colonPos === false && $dotPos === false) {
            return [null, $tagName];
        }

        if ($colonPos === false) {
            $colonPos = PHP_INT_MAX;
        }

        if ($dotPos === false) {
            $dotPos = PHP_INT_MAX;
        }

        $delimiterPos = min($colonPos, $dotPos);

        $prefix = mb_substr($tagName, 0, $delimiterPos);
        $suffix = mb_substr($tagName, $delimiterPos + 1);

        return [$prefix, $suffix];
    }
}
