<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Compiler\Extractions;
use Stillat\Dagger\Compiler\TemplateCompiler;
use Stillat\Dagger\Support\Utils;

trait CompilesStencils
{
    protected array $stencilPlaceholders = [];

    protected function compileStencils(ComponentState $componentState, Extractions $extractions, string $compiledComponent): string
    {
        $namedTemplates = $componentState->getNamedTemplates();

        if (empty($namedTemplates)) {
            return $compiledComponent;
        }

        $defaultMapping = [];
        foreach ($namedTemplates as $stencilName => $templateParts) {
            $defaultMapping[$stencilName] = $templateParts[0];
        }

        foreach ($namedTemplates as $templateName => [$defaultContent, $placeholder]) {
            if (! isset($extractions->stencils[$templateName])) {
                $compiledComponent = Str::replace($placeholder, $defaultContent, $compiledComponent);

                continue;
            }

            [$customContent, $substitutions] = $extractions->stencils[$templateName];

            $templateToUse = $customContent ?? $defaultContent;

            foreach ($substitutions as $defaultPlaceholder => $subStencilName) {
                $stencilDefaultValue = $defaultMapping[$subStencilName] ?? '';
                $templateToUse = Str::replace($defaultPlaceholder, $stencilDefaultValue, $templateToUse);
            }

            $compiledComponent = Str::replace($placeholder, $templateToUse, $compiledComponent);
        }

        return $compiledComponent;
    }

    protected function extractStencil(ComponentNode $stencil): ?array
    {
        if ($stencil->isSelfClosing || $stencil->isClosedBy === null) {
            return null;
        }

        return $this->extractDefaultStencils($stencil);
    }

    protected function extractDefaultStencils(ComponentNode $component): array
    {
        $content = '';
        $defaultReplacements = [];

        foreach ($component->childNodes as $child) {
            if (! $child instanceof ComponentNode) {
                $content .= $this->getNodeContent($child);

                continue;
            }

            if (! $child->isSelfClosing) {
                $content .= $this->compileBasicComponent($child);

                continue;
            }

            [$componentPrefix] = $this->getPrefixedComponentName($child);

            if ($componentPrefix !== TemplateCompiler::STENCIL || ! Str::endsWith($child->tagName, '.default')) {
                $content .= $this->compileBasicComponent($child);

                continue;
            }

            $defaultPlaceholder = '__stencil::default::'.Utils::makeRandomString();
            $this->stencilPlaceholders[$defaultPlaceholder] = 1;

            $content .= $defaultPlaceholder;

            $templateName = Utils::normalizeComponentName(
                mb_substr($child->tagName, 0, mb_strlen($child->tagName) - 8)
            );

            $defaultReplacements[$defaultPlaceholder] = $templateName;
        }

        return [trim($content), $defaultReplacements];
    }
}
