<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use PhpParser\Error;
use PhpParser\PrettyPrinter\Standard;
use ReflectionMethod;
use Stillat\BladeParser\Nodes\AbstractNode;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\LiteralNode;
use Stillat\BladeParser\Parser\DocumentParser;
use Stillat\Dagger\Compiler\Concerns\AppliesCompilerParams;
use Stillat\Dagger\Compiler\Concerns\CompilesBasicComponents;
use Stillat\Dagger\Compiler\Concerns\CompilesCache;
use Stillat\Dagger\Compiler\Concerns\CompilesCompilerAttributes;
use Stillat\Dagger\Compiler\Concerns\CompilesCompilerDirectives;
use Stillat\Dagger\Compiler\Concerns\CompilesComponentDetails;
use Stillat\Dagger\Compiler\Concerns\CompilesDynamicComponents;
use Stillat\Dagger\Compiler\Concerns\CompilesForwardedAttributes;
use Stillat\Dagger\Compiler\Concerns\CompilesPhp;
use Stillat\Dagger\Compiler\Concerns\CompilesSlots;
use Stillat\Dagger\Compiler\Concerns\CompilesStencils;
use Stillat\Dagger\Compiler\Concerns\ManagesComponentCtrState;
use Stillat\Dagger\Compiler\Concerns\ManagesExceptions;
use Stillat\Dagger\Exceptions\CompilerException;
use Stillat\Dagger\Exceptions\CompilerRenderException;
use Stillat\Dagger\Exceptions\ComponentException;
use Stillat\Dagger\Exceptions\InvalidCompilerParameterException;
use Stillat\Dagger\Exceptions\Mapping\LineMapper;
use Stillat\Dagger\Exceptions\MissingComponentNamespaceException;
use Stillat\Dagger\Facades\SourceMapper;
use Stillat\Dagger\Parser\ComponentCache;
use Stillat\Dagger\Parser\ComponentParser;
use Stillat\Dagger\Runtime\ViewManifest;
use Stillat\Dagger\Support\Utils;
use Throwable;

final class TemplateCompiler
{
    public const STENCIL = 'stencil';

    use AppliesCompilerParams,
        CompilesBasicComponents,
        CompilesCache,
        CompilesCompilerAttributes,
        CompilesCompilerDirectives,
        CompilesComponentDetails,
        CompilesDynamicComponents,
        CompilesForwardedAttributes,
        CompilesPhp,
        CompilesSlots,
        CompilesStencils,
        ManagesComponentCtrState,
        ManagesExceptions;

    protected array $compilerDirectiveParams = [
        '#id', '#name', '#compiler',
        '#style', '#def', '#group',
        '#styledef', '#classdef',
        '#cache', '#precomile',
        '#for', '#when'
    ];

    protected ReflectionMethod $storeRawBlockProxy;

    protected array $componentNamespaces = ['c'];

    protected AttributeCompiler $attributeCompiler;

    protected ComponentParser $componentParser;

    protected ComponentCompiler $componentCompiler;

    protected ViewManifest $manifest;

    protected array $componentStack = [];

    protected array $activeComponentNames = [];

    protected array $componentPath = [];

    protected BladeCompiler $compiler;

    protected int $compilerDepth = 0;

    protected array $componentBlocks = [];

    protected array $forwardedProperties = [];

    protected array $forwardedSlots = [];

    protected array $forwardedSlotPaths = [];

    protected Factory $viewFactory;

    protected string $newlineStyle = "\n";

    protected Standard $printer;

    protected ReplacementManager $replacementManager;

    protected LineMapper $lineMapper;

    protected ?ComponentState $activeComponent = null;

    protected CompilerOptions $options;

    protected Renderer $renderer;

    protected ?string $currentViewName = null;

    protected ?string $currentCachePath = null;

    protected bool $enabled = true;

    protected bool $ctrEnabled = true;

    public function __construct(ViewManifest $manifest, Factory $factory, LineMapper $lineMapper, string $cachePath)
    {
        $this->options = new CompilerOptions;

        $this->lineMapper = $lineMapper;
        $this->manifest = $manifest;
        $this->viewFactory = $factory;
        $this->options->viewCachePath = Str::finish(Utils::normalizePath($cachePath), '/');

        $this->storeRawBlockProxy = new ReflectionMethod(BladeCompiler::class, 'storeRawBlock');
        $this->storeRawBlockProxy->setAccessible(true);

        $this->printer = new Standard;
        $this->replacementManager = new ReplacementManager;
        $this->componentCompiler = new ComponentCompiler;
        $this->componentParser = new ComponentParser(new ComponentCache);
        $this->compiler = Blade::getFacadeRoot();
        $this->attributeCompiler = new AttributeCompiler;
        $this->renderer = new Renderer($this, $this->compiler);
    }

    public function getOptions(): CompilerOptions
    {
        return $this->options;
    }

    public function setOptions(CompilerOptions $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Sets if line numbers should be added to compiled output.
     *
     * Templates with line numbers will not produce the same output
     * and should not be used outside error reporting scenarios.
     */
    public function compileComponentLineNumbers(bool $compileLineNumbers): static
    {
        $this->options->addComponentLineNumbers = $compileLineNumbers;

        return $this;
    }

    /**
     * Store a row block and return a unique placeholder.
     */
    protected function storeRawBlock(string $value): string
    {
        return $this->storeRawBlockProxy->invoke($this->compiler, $value);
    }

    /**
     * Determines if the compiler is compiling the top-most template.
     */
    protected function isRoot(): bool
    {
        return $this->compilerDepth === 0;
    }

    /**
     * Replaces all raw placeholders within the provided string.
     */
    public function resolveBlocks(string $value): string
    {
        $placeholders = array_keys($this->componentBlocks);

        while (Str::contains($value, $placeholders)) {
            $value = Str::swap($this->componentBlocks, $value);
        }

        return $value;
    }

    /**
     * Determines if the provided component contains any registered component tag prefixes.
     */
    protected function containsRegisteredComponentTags(ComponentNode $component): bool
    {
        if (
            $component->isClosedBy != null &&
            ! $component->isSelfClosing &&
            Str::contains($component->innerDocumentContent, $this->componentPrefixes)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves the original template text of the provided node.
     */
    protected function getNodeContent(AbstractNode $node): string
    {
        if ($node instanceof LiteralNode) {
            return $node->unescapedContent;
        }

        if ($node instanceof ComponentNode) {
            if (! $node->isSelfClosing && $node->isClosedBy != null) {
                return $node->outerDocumentContent;
            }
        }

        return $node->content;
    }

    /**
     * Generates a forwarded component path for the current component.
     *
     * These paths are used to apply forwarded
     * to nested components at compile time.
     */
    protected function getForwardedComponentPath(): string
    {
        $parts = $this->componentPath;
        $partsToUse = [];

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            // If we encounter a component without
            // an explicit compiler ID, we want
            // to reset the component path.
            if (str_starts_with($part, '{')) {
                if ($i > 0 && count($partsToUse)) {
                    $partsToUse = [];
                }

                continue;
            }

            $partsToUse[] = $part;
        }

        return implode('', $partsToUse);
    }

    /**
     * @throws InvalidCompilerParameterException
     */
    protected function startCompilingComponent(ComponentState $state, array $compilerParams): void
    {
        $this->applyCompilerParameters($state, $compilerParams);

        $this->componentStack[] = $state;
        $this->activeComponentNames[] = $this->getComponentName($state->node);
        $this->activeComponent = $state;

        $this->componentPath[] = $state->compilerId;
    }

    protected function stopCompilingComponent(): void
    {
        $this->activeComponent->injectedProps = [];

        array_pop($this->componentStack);
        array_pop($this->componentPath);
        array_pop($this->activeComponentNames);

        if (count($this->componentStack) > 0) {
            $this->activeComponent = $this->componentStack[array_key_last($this->componentStack)];
        } else {
            $this->cleanupAfterComponentStack();
        }
    }

    protected function cleanupAfterComponentStack(): void
    {
        $this->renderer->reset();
    }

    /**
     * @internal
     */
    public function disableCompileTimeRenderOnStack(): void
    {
        $this->ctrEnabled = false;
    }

    protected function compileBoundScopeVariables(): string
    {
        $depVars = '';

        /** @var ComponentState $componentState */
        foreach ($this->componentStack as $componentState) {
            foreach ($componentState->boundScopeVariables as $varName) {
                $depVars .= "'{$varName}' => \${$varName},";
            }
        }

        return $depVars;
    }

    /**
     * @internal
     */
    public function hasBoundScopeVariables(): bool
    {
        /** @var ComponentState $componentState */
        foreach ($this->componentStack as $componentState) {
            if (! empty($componentState->boundScopeVariables)) {
                return true;
            }
        }

        return false;
    }

    protected function incrementCompilerDepth(): void
    {
        $this->compilerDepth += 1;
    }

    protected function decrementCompilerDepth(): void
    {
        $this->compilerDepth = max(--$this->compilerDepth, 0);
    }

    protected function getDynamicStrings(): array
    {
        return array_merge(
            array_keys($this->stencilPlaceholders),
            array_keys($this->componentBlocks),
            $this->componentPrefixes
        );
    }

    protected function isDynamicComponent(ComponentNode $node): bool
    {
        return in_array(mb_strtolower($node->tagName), ['proxy', 'dynamic-component', 'delegate-component']);
    }

    /**
     * @throws CompilerException
     * @throws InvalidCompilerParameterException
     * @throws MissingComponentNamespaceException|ComponentException
     */
    protected function compileNodes(array $nodes): string
    {
        if (empty($nodes)) {
            return '';
        }

        $compiled = '';

        $lastNodeIndex = $nodes[array_key_last($nodes)]->index;

        foreach ($nodes as $node) {
            if (! $node instanceof ComponentNode) {
                $compiled .= $node->content;

                continue;
            }

            $compilerParams = $this->filterParameters($node);

            if (! in_array($node->componentPrefix, $this->componentNamespaces)) {
                if ($this->containsRegisteredComponentTags($node)) {
                    $compiled .= $node->content;
                    $compiled .= $this->compileNodes($node->childNodes);
                    $compiled .= $node->isClosedBy->content;

                    continue;
                }

                $compiled .= $this->compileBasicComponent($node);

                continue;
            }

            $currentView = $this->manifest->last();
            $currentViewPath = $currentView?->getPath();

            if ($this->isDynamicComponent($node)) {
                $compiled .= $this->compileDynamicComponentScaffolding($node, $currentViewPath ?? '');

                continue;
            }

            [$componentNamePrefix] = $this->getPrefixedComponentName($node);

            if ($componentNamePrefix == TemplateCompiler::STENCIL && ! $this->isRoot()) {
                throw SourceMapper::makeComponentCompilerException(
                    $node,
                    'Stencils may only appear within components.',
                    $currentViewPath ?? ''
                );
            }

            if ($node->tagName === 'compiler:template_end') {
                if ($node->index != $lastNodeIndex) {
                    throw SourceMapper::makeComponentCompilerException(
                        $node,
                        'Compiler component [compiler:template_end] must be the last component.',
                        $currentViewPath ?? ''
                    );
                }

                foreach ($this->componentBlocks as $placeholder => $blockValue) {
                    $laravelRaw = $this->storeRawBlock($this->resolveBlocks($blockValue));

                    $compiled = Str::replace($placeholder, $laravelRaw, $compiled);
                }

                $this->decrementCompilerDepth();

                return $compiled;
            }

            $currentComponentName = $this->getComponentName($node);

            if (in_array($currentComponentName, $this->activeComponentNames)) {
                $compiled .= $this->compileCircularComponent($node, $currentViewPath ?? '');

                continue;
            }

            $varSuffix = Utils::makeRandomString();
            $view = $this->manifest->withoutTracing(fn () => $this->resolveView($node));
            $sourcePath = $view->getPath();
            $componentModel = $this->compileView($sourcePath, $node, $varSuffix);

            $cachePath = null;

            $this->startCompilingComponent($componentModel, $compilerParams);

            $this->compileForwardedAttributes($node);

            if ($currentView) {
                $this->activeComponent->viewPath = $currentViewPath;
                $cachePath = $this->compiler->getCompiledPath($currentViewPath);

                $this->manifest->track(
                    $currentView->name(),
                    $cachePath,
                    $sourcePath
                );
            }

            $slotContainerVar = '__slotContainer'.$varSuffix;

            $this->activeComponent->extractions = $this->extractDetails($node);
            $innerContent = $this->activeComponent->extractions->content;

            $compiledSlots = $this->compileForwardedSlots($this->activeComponent->extractions->forwardedSlots);
            $compiledSlots .= $this->compileSlotContent($innerContent, $slotContainerVar);

            $compiledSlots .= $this->compileNamedSlots($this->activeComponent->extractions->namedSlots, $slotContainerVar);

            if ($this->activeComponent->hasUserSuppliedId) {
                $compiledSlots .= $this->forwardSlots($slotContainerVar);
            }

            foreach ($componentModel->getNamedTemplates() as $stencil) {
                $this->stencilPlaceholders[$stencil[1]] = 1;
            }

            $innerTemplate = $componentModel->getTemplate();
            $innerTemplate = $this->compileStencils($componentModel, $this->activeComponent->extractions, $innerTemplate);

            $compiledComponentParams = $this->attributeCompiler->compile(
                $this->filterComponentParams($node->parameters ?? []),
                $componentModel->getPropNames(),
            );

            $this->activeComponent->compiledComponentAttributes = $this->attributeCompiler->getLastCompiledNames();

            $compiledComponentTemplate = <<<'PHP'
<?php
try {
$__componentData = \Stillat\Dagger\Runtime\Attributes::mergeNestedAttributes($compiledParams, $compiledPropNames);
/** COMPILE:INJECTED_PROPS */
/** COMPILE:PROPS_DEFAULT */
/** COMPILE_IF_VAR:componentGlobalScope $targetVar = get_defined_vars(); */
$__slotContainerVarSuffix = new \Stillat\Dagger\Runtime\SlotContainer;
/** COMPILE_IF_VAR:componentValueForwardingContainer $targetVar = new \Stillat\Dagger\Runtime\Forwarding\ValueContainer; */
/** COMPILE_IF_VAR:componentValueForwardingContainer #forwardedProperties */

/** COMPILE:COMPONENT_START */

if (isset($attributes)) { $__attributesOriginalVarSuffix = $attributes; }
if (isset($componentVarName)) { $__componentOriginalVarSuffix = $componentVarName; }
$attributes = new \Illuminate\View\ComponentAttributeBag(array_diff_key($__componentData, $compiledPropNames));
/** COMPILE:AWARE */
/** COMPILE:EXTRACT_PROP_VALUES_FROM_PARAMS */

$componentVarName = new \Stillat\Dagger\Runtime\Component(
    null,
    '#componentName#',
    $attributes,
    new \Illuminate\Support\Fluent($__componentData),
    $__slotContainerVarSuffix,
    \Stillat\Dagger\Facades\ComponentEnv::last(),
    \Stillat\Dagger\Facades\ComponentEnv::depth(),
);
/** COMPILE:INJECTIONS */
/** COMPILE:PROP_VALIDATION */
\Stillat\Dagger\Facades\ComponentEnv::push($__env, $componentVarName);

if (isset($component)) { $__componentSlotReplacementsOriginalVarSuffix = $componentVarName; }
$component = $componentVarName;
?>#slots#<?php
if (isset($__componentSlotReplacementsOriginalVarSuffix)) { $component = $__componentSlotReplacementsOriginalVarSuffix; }
ob_start();
$__tmpVars = [
    '__env' => $__env,
    'componentVarName' => $componentVarName,
    'attributes' => $attributes,
    $dependentVars,
];

$__componentData['slots'] = $__slotContainerVarSuffix;
if ($__slotContainerVarSuffix->hasDefaultSlotContent()) { $__componentData['slot'] = $__slotContainerVarSuffix->getDefaultContent(); }

(function () use ($__tmpVars, $__componentData) {
$slot = \Stillat\Dagger\Runtime\SlotContainer::getEmptySlot();
extract($__tmpVars);extract($componentVarName->getMacros());extract($__componentData);extract($componentVarName->data->toArray());
?>#inner#<?php
})();
$__result = ob_get_clean();
/** COMPILE:COMPONENT_RESULT */
\Stillat\Dagger\Facades\ComponentEnv::pop($__env);
/** COMPILE:OUTPUT */
unset($__tmpVars); unset($__result, $componentVarName); unset($attributes, $__componentData, $__slotContainerVarSuffix);

if (isset($__attributesOriginalVarSuffix)) { $attributes = $__attributesOriginalVarSuffix; unset($__attributesOriginalVarSuffix); }
if (isset($__componentOriginalVarSuffix)) { $componentVarName = $__componentOriginalVarSuffix; unset($__componentOriginalVarSuffix); }
/** COMPILE:VAR_CLEANUP */
/** COMPILE:COMPONENT_END */
} catch (\Throwable $e) {
    if (! \Stillat\Dagger\Support\Utils::arePathsEqual($e->getFile(), __FILE__)) { throw $e; }
    throw \Stillat\Dagger\Facades\SourceMapper::convertException(
        $e,
        '#sourcePath#',
        '#rootPath#',
        '#componentTrace#'
    );
}
?>
PHP;

            try {
                $innerContent = $this->componentCompiler->compile(
                    $this->compiler->compileString($this->compile($innerTemplate))
                );

                try {
                    $this->checkForCtrEligibility($innerTemplate, $innerContent);
                } catch (Throwable) {
                    $this->activeComponent->isCtrEligible = false;
                }
            } catch (Error $error) {
                throw SourceMapper::convertParserError($error, $innerTemplate, $sourcePath, $componentModel->lineOffset);
            }

            if (count($this->componentCompiler->getRenders()) > 0) {
                foreach ($this->componentCompiler->getRenders() as $render) {
                    $placeholder = $render['placeholder'];
                    $phpNode = $render['node'];

                    $this->activeComponent->cacheReplacements[$placeholder] = '<?php echo '.$this->printPhpAst($phpNode).'; ?>';
                }
            }

            if (count($this->activeComponent->cacheReplacements) > 0) {
                $this->activeComponent->getGlobalScopeVariableName();
            }

            $compiledComponentTemplate = $this->compileCompilerAttributes($compiledComponentTemplate);
            $compiledComponentTemplate = $this->compileCompilerDirectives($compiledComponentTemplate);

            $propNames = array_flip($componentModel->getAllPropNames());

            $swapVars = [
                '#cachePath#' => $cachePath ?? '',
                '#sourcePath#' => Utils::normalizePath($sourcePath),
                '#rootPath#' => $this->manifest->getRootView()?->getPath() ?? '',
                '__cacheKeyPrefix' => $this->activeComponent->getCachePrefix(),
                '$__result' => '$'.$this->activeComponent->getOutputVar(),
                '$__componentData' => '$'.$this->activeComponent->componentDataVar(),
                '$__tmpVars' => '$'.$this->activeComponent->getTemporaryRenderVar(),
                '#slots#' => $compiledSlots,
                'VarSuffix' => $varSuffix,
                '#componentName#' => $node->tagName,
                '$dependentVars,' => $this->compileBoundScopeVariables(),
                '$compiledPropNames' => Str::squish($this->compilePhpArray($propNames)),
                'componentVarName' => $componentModel->getVariableName(),
                '$compiledParams' => $compiledComponentParams,
                '#inner#' => $this->storeComponentBlock($innerContent),
            ];

            $compiledComponentTemplate = Str::swap($swapVars, $compiledComponentTemplate);

            if (! $this->ctrEnabled || ! $this->renderer->canRender($this->activeComponent)) {
                $compiled .= $this->finalizeCompiledComponent($compiledComponentTemplate);
            } else {
                $this->enabled = false;
                try {
                    $renderedResult = $this->renderer->render(
                        $this->activeComponent,
                        $this->resolveBlocks($compiledComponentTemplate)
                    );

                    $compiled .= $this->storeComponentBlock($renderedResult);
                } catch (CompilerRenderException) {
                    $compiled .= $this->finalizeCompiledComponent($compiledComponentTemplate);
                } finally {
                    $this->enabled = true;
                }
            }

            $this->stopCompilingComponent();
        }

        $this->compilerDepth -= 1;

        if ($this->isRoot()) {
            $this->stencilPlaceholders = [];
        }

        return $compiled;
    }

    protected function finalizeCompiledComponent(string $compiled): string
    {
        $compiled = $this->compileExceptions($compiled);

        if ($this->activeComponent->cacheProperties != null) {
            $compiled = $this->compileCache($compiled);
        }

        return $this->storeComponentBlock($compiled);
    }

    public function cleanup(): void
    {
        $this->componentParser->getComponentCache()->clear();
        $this->renderer->clear();
        $this->replacementManager->clear();

        $this->componentBlocks = [];
    }

    protected function compileVariableCleanup(array $variables): string
    {
        return collect($variables)
            ->map(fn ($variable) => $this->compileVariableUnset($variable))
            ->join($this->newlineStyle);
    }

    protected function compileVariableUnset(string $variableName): string
    {
        return "if (isset({$variableName})) { unset({$variableName}); }";
    }

    protected function resetCompilerState(): void
    {
        $this->ctrEnabled = true;
        $this->compilerDepth = 0;
    }

    /**
     * Compiles the provided template with line numbers.
     */
    public function compileWithLineNumbers(string $template): string
    {
        $this->compileComponentLineNumbers(true);

        try {
            return $this->compiler->compileString($template);
        } finally {
            $this->compileComponentLineNumbers(false);
        }
    }

    /**
     * @throws CompilerException
     */
    public function compile(string $template): string
    {
        if (! $this->enabled) {
            return $template;
        }

        $this->newlineStyle = Utils::getNewlineStyle($template);

        if ($this->isRoot()) {
            $this->resetCompilerState();
            $template .= '<c-compiler:template_end />';
        }

        $this->incrementCompilerDepth();

        $nodes = (new DocumentParser)
            ->registerCustomComponentTags($this->componentNamespaces)
            ->onlyParseComponents()
            ->parseTemplate($template)
            ->toDocument()
            ->getNodes()
            ->all();

        return $this->compileNodes($this->simplifyNodes($nodes));
    }

    protected function simplifyNodes(array $nodes): array
    {
        /** @var AbstractNode $node */
        foreach ($nodes as $node) {
            $node->setDocument(null);
            $node->previousNode = $node->nextNode = null;
            $node->structure = null;

            if ($node instanceof ComponentNode) {
                $node->namePosition = null;
                $node->parameterContentPosition = null;
            }
        }

        return collect($nodes)
            ->where(fn (AbstractNode $node) => $node->parent == null)
            ->values()
            ->all();
    }

    protected function storeComponentBlock(string $value): string
    {
        $placeholder = '__DAGGER_RAW::'.Utils::makeRandomString();
        $this->componentBlocks[$placeholder] = $value;

        return $placeholder;
    }
}
