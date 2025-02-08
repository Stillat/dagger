<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Compiler\ExtractedSlot;
use Stillat\Dagger\Compiler\Extractions;
use Stillat\Dagger\Compiler\TemplateCompiler;
use Stillat\Dagger\Exceptions\InvalidArgumentException;
use Stillat\Dagger\Exceptions\MissingComponentNamespaceException;
use Stillat\Dagger\Support\Utils;

trait CompilesComponentDetails
{
    protected array $componentPrefixes = [];

    protected array $viewCache = [];

    protected array $prefixNamespaces = [];

    /**
     * @throws InvalidArgumentException
     */
    protected function assertPrefixIsNotLaravelPrefix(string $componentPrefix): void
    {
        if ($componentPrefix !== 'x') {
            return;
        }

        throw new InvalidArgumentException('Cannot register [x] as a component prefix.');
    }

    protected function getComponentHash(ComponentNode $node): string
    {
        $value = $node->content;

        if ($node->isClosedBy != null) {
            $value .= $node->innerDocumentContent;
        }

        return md5($value);
    }

    protected function getComponentName(ComponentNode $node): string
    {
        return "{$node->componentPrefix}:{$node->tagName}";
    }

    /**
     * @throws InvalidArgumentException
     */
    public function registerComponentPath(string $componentPrefix, string $path, ?string $namespace = null): void
    {
        $this->assertPrefixIsNotLaravelPrefix($componentPrefix);

        if (! in_array($componentPrefix, $this->componentNamespaces)) {
            $this->componentNamespaces[] = $componentPrefix;
        }

        if ($namespace === null) {
            $namespace = md5($componentPrefix);
        }

        $this->prefixNamespaces[mb_strtolower($componentPrefix)] = $namespace;
        $this->componentPrefixes[] = "<{$componentPrefix}:";
        $this->componentPrefixes[] = "<{$componentPrefix}-";

        $this->addComponentNamespacePath($namespace, $path);
    }

    /**
     * @throws MissingComponentNamespaceException
     */
    protected function assertComponentPrefixExists(string $componentPrefix): string
    {
        $componentPrefix = mb_strtolower($componentPrefix);

        if (! isset($this->prefixNamespaces[$componentPrefix])) {
            throw new MissingComponentNamespaceException("View namespace not available for [{$componentPrefix}]");
        }

        return $componentPrefix;
    }

    /**
     * @throws MissingComponentNamespaceException
     */
    protected function getComponentNamespace(string $componentPrefix): string
    {
        return $this->prefixNamespaces[$this->assertComponentPrefixExists($componentPrefix)];
    }

    /**
     * @throws MissingComponentNamespaceException
     * @throws InvalidArgumentException
     */
    public function addComponentViewPath(string $componentPrefix, string $path): void
    {
        $this->assertPrefixIsNotLaravelPrefix($componentPrefix);

        $this->addComponentNamespacePath(
            $this->getComponentNamespace($componentPrefix),
            $path
        );
    }

    public function addComponentNamespacePath(string $namespace, string $path): void
    {
        $this->viewFactory->addNamespace($namespace, $path);
    }

    /**
     * @throws MissingComponentNamespaceException
     */
    public function resolveView(ComponentNode $node): View
    {
        $cacheKey = $node->componentPrefix.'::'.$node->name;

        if (isset($this->viewCache[$cacheKey])) {
            return $this->viewCache[$cacheKey];
        }

        $viewName = $this->guessViewName(
            $this->getComponentNamespace($node->componentPrefix),
            $node->name
        );

        return $this->viewCache[$cacheKey] = view($viewName);
    }

    protected function guessViewName(string $namespace, string $componentName): string
    {
        $prefix = "{$namespace}::{$componentName}";

        $guesses = [
            $prefix,
            $prefix.'.index',
            $prefix.'.'.$componentName,
        ];

        foreach ($guesses as $guess) {
            if ($this->viewFactory->exists($guess)) {
                return $guess;
            }
        }

        return $prefix;
    }

    protected function compileCacheReplacements(): string
    {
        $result = '';

        $cacheReplacements = $this->activeComponent->cacheReplacements;

        if (! empty($cacheReplacements)) {
            $result .= $this->buildCacheReplacements($cacheReplacements);
        }

        $placeholders = $this->replacementManager->getPlaceholders($this->activeComponent);

        if (! empty($placeholders)) {
            $result .= $this->buildEmptyStringReplacements($placeholders);
        }

        return $result;
    }

    protected function buildCacheReplacements(array $cacheReplacements): string
    {
        $content = <<<'PHP'
(function () use ($__componentData, &$__result, $globalScope, $componentVarName) { extract($globalScope); extract($__componentData); $component = $componentVarName;
PHP;

        $content = Str::swap([
            'globalScope' => $this->activeComponent->getGlobalScopeVariableName(),
        ], $content);

        $replacementStub = <<<'PHP'
ob_start() ?>#inner#<?php
$__result = str_replace('$needle', ob_get_clean(), $__result);
PHP;

        foreach ($cacheReplacements as $needle => $replacement) {
            $content .= Str::swap([
                '$needle' => $needle,
                '#inner#' => $replacement,
            ], $replacementStub);
        }

        $content .= ' })(); ';

        return $content;
    }

    protected function buildEmptyStringReplacements(array $placeholders): string
    {
        $emptyStringReplacements = [];

        foreach ($placeholders as $placeholder) {
            $emptyStringReplacements[$placeholder] = '';
        }

        $emptyReplace = <<<'PHP'
$__result = strtr($__result, $replacements);
PHP;

        return Str::swap([
            '$replacements' => $this->compilePhpArray($emptyStringReplacements),
        ], $emptyReplace);
    }

    protected function compileComponentOutput(): string
    {
        $componentOutput = $this->compileCacheReplacements();

        if ($this->activeComponent->getTrimOutput()) {
            return $componentOutput.'echo trim($__result);';
        }

        return $componentOutput.'echo $__result;';
    }

    protected function compileView(string $path, ComponentNode $component, string $varSuffix): ComponentState
    {
        $contents = file_get_contents($path);

        if ($this->options->addComponentLineNumbers) {
            $contents = $this->lineMapper->insertLineNumbers($contents);
        }

        $this->componentParser->setEvaluateModel(! $this->options->addComponentLineNumbers);

        $contents = $this->compileBladePhpBlocks($contents);

        $model = $this->componentParser->setComponentNamespaces($this->componentNamespaces)->parse(
            $component,
            $contents,
            $varSuffix,
            $path
        );

        $model->componentPath = Utils::normalizePath($path);

        return $model;
    }

    protected function extractDetails(ComponentNode $component): Extractions
    {
        $extractions = new Extractions;

        foreach ($component->childNodes as $node) {
            if (! $node instanceof ComponentNode) {
                $extractions->content .= $this->getNodeContent($node);

                continue;
            }

            $this->handleComponentNode($component, $node, $extractions);
        }

        $extractions->content = trim($extractions->content);

        return $extractions;
    }

    protected function handleComponentNode(ComponentNode $parent, ComponentNode $node, Extractions $extractions): void
    {
        [$componentPrefix] = $this->getPrefixedComponentName($node);

        if ($componentPrefix === TemplateCompiler::STENCIL && $node->parent === $parent) {
            $this->extractStencils($node, $extractions);

            return;
        }

        if ($node->parent === $parent && mb_strtolower($node->tagName) === 'slot') {
            $this->extractSlots($node, $extractions);

            return;
        }

        // Default: append the node's content to the extraction
        $extractions->content .= $this->getNodeContent($node);
    }

    protected function extractStencils(ComponentNode $node, Extractions $extractions): void
    {
        if ($stencil = $this->extractStencil($node)) {
            $normalizedName = Utils::normalizeComponentName($node->tagName);
            $extractions->stencils[$normalizedName] = $stencil;
        }
    }

    /**
     * Processes a slot node, determining if it is forwarded or named.
     */
    protected function extractSlots(ComponentNode $node, Extractions $extractions): void
    {
        $slot = new ExtractedSlot($node);

        if ($slot->isForwardedSlot()) {
            $extractions->forwardedSlots[] = $slot;

            return;
        }

        $extractions->namedSlots[] = $slot;
    }
}
