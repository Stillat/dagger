<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\Dagger\Exceptions\CompilerRenderException;
use Stillat\Dagger\Runtime\Cache\AttributeCache;
use Stillat\Dagger\Support\Utils;
use Throwable;

final class Renderer
{
    protected array $disabledComponents = [];

    protected array $pendingProps = [];

    protected array $renderCache = [];

    protected TemplateCompiler $compiler;

    protected BladeCompiler $bladeCompiler;

    public function __construct(TemplateCompiler $compiler, BladeCompiler $bladeCompiler)
    {
        $this->compiler = $compiler;
        $this->bladeCompiler = $bladeCompiler;
    }

    public function reset(): void
    {
        $this->pendingProps = [];
    }

    public function clear(): void
    {
        $this->reset();

        $this->disabledComponents = [];
    }

    protected function containsDynamicAttributes(ComponentNode $component): bool
    {
        foreach ($component->parameters as $param) {
            if (
                $param->type == ParameterType::DynamicVariable ||
                $param->type == ParameterType::ShorthandDynamicVariable ||
                $param->type == ParameterType::AttributeEcho ||
                $param->type == ParameterType::AttributeRawEcho ||
                $param->type == ParameterType::AttributeTripleEcho ||
                $param->type == ParameterType::UnknownEcho ||
                $param->type == ParameterType::UnknownRawEcho ||
                $param->type == ParameterType::UnknownTripleEcho
            ) {
                return true;
            }
        }

        return false;
    }

    protected function attributesSatisfyProps(ComponentState $component, array $propNames): bool
    {
        $attributeNames = collect($component->node->parameters)
            ->map(fn (ParameterNode $param) => $param->materializedName)
            ->unique()
            ->flip()
            ->all();

        $defaultProps = array_flip(array_keys($component->getPropDefaults()));

        foreach ($propNames as $propName) {
            if (isset($this->pendingProps[$propName])) {
                // We can satisfy this one now. Let's clear it out.
                unset($this->pendingProps[$propName]);
            }

            if (isset($attributeNames[$propName])) {
                continue;
            }

            if (isset($defaultProps[$propName])) {
                continue;
            }

            $this->pendingProps[$propName] = true;
        }

        if (! empty($this->pendingProps)) {
            return false;
        }

        return true;
    }

    protected function canRenderSlots(ComponentState $componentState): bool
    {
        if ($componentState->extractions === null) {
            return true;
        }

        if ($componentState->extractions->content != '') {
            return false;
        }

        if (! empty($componentState->extractions->forwardedSlots)) {
            return false;
        }

        if (! empty($componentState->extractions->namedSlots)) {
            return false;
        }

        return true;
    }

    public function canRender(ComponentState $componentState): bool
    {
        if (isset($this->disabledComponents[$componentState->componentPath])) {
            return false;
        }

        if (! $componentState->isCtrEligible) {
            // The parsers have determined that this component
            // is not eligible for compile time rendering.
            // This is typically due to either nested
            // components also not being eligible.
            $this->compiler->disableCompileTimeRenderOnStack();

            return false;
        }

        if ($componentState->shouldCache) {
            // If a developer has explicitly indicated
            // the cache is enabled, assume they are
            // doing things the way they want to
            $this->compiler->disableCompileTimeRenderOnStack();

            return false;
        }

        if ($componentState->hasMixins()) {
            // Mixins may contain data that is resolved
            // only at runtime. Because of this, we
            // cannot assume it is safe to CTR.
            $this->compiler->disableCompileTimeRenderOnStack();

            return false;
        }

        if (! empty($componentState->cacheReplacements)) {
            // The existence of cache replacements are
            // a good indicator of dynamic behavior
            $this->compiler->disableCompileTimeRenderOnStack();

            return false;
        }

        if ($this->compiler->hasBoundScopeVariables()) {
            // We won't disable CTR on the stack at this time.
            // It is possible that parents can satisfy the
            // compile time rendering requirements...
            return false;
        }

        if (! $this->canRenderSlots($componentState)) {
            // Slots may be evaluated in an unresolvable state
            // at compile time, therefore we disable CTR.
            $this->compiler->disableCompileTimeRenderOnStack();

            return false;
        }

        if ($this->containsDynamicAttributes($componentState->node)) {
            $this->compiler->disableCompileTimeRenderOnStack();

            return false;
        }

        if (empty($componentState->getPropNames()) && empty($componentState->getAwareVariables())) {
            return true;
        }

        if ($this->attributesSatisfyProps($componentState, $componentState->getAllPropNames())) {
            return true;
        }

        return false;
    }

    protected function normalizeTemplate(ComponentState $componentState, string $template): string
    {
        $replacements = [
            $componentState->varSuffix => '_ctrVarSuffix',
        ];

        foreach ($componentState->getDynamicVariables() as $varName => $value) {
            $replacements[$value] = '__ctrRep'.$varName;
        }

        return Str::swap($replacements, $template);
    }

    protected function getTemporaryPath(): string
    {
        return $this->compiler->getOptions()->viewCachePath.Utils::makeRandomString().'.php';
    }

    protected function renderFile(string $path)
    {
        $__env = view();

        return (static function () use ($path, $__env) {
            return require $path;
        })();
    }

    protected function reduceAttributes(ComponentNode $component): array
    {
        // If we are calling this method, we should know
        // that the attributes/props supplied to the
        // component are all static. It is safe
        // to convert simplify to key/value
        return collect($component->parameters)
            ->sortBy(fn (ParameterNode $param) => $param->materializedName)
            ->mapWithKeys(fn (ParameterNode $param) => [$param->materializedName => $param->value])
            ->all();
    }

    /**
     * @throws CompilerRenderException
     */
    public function render(ComponentState $componentState, string $template): string
    {
        $template = $this->normalizeTemplate($componentState, $template);

        $key = implode('', [
            md5($template),
            AttributeCache::getAttributeCacheKey($this->reduceAttributes($componentState->node)),
        ]);

        if (isset($this->renderCache[$key])) {
            return $this->renderCache[$key];
        }

        $obLevel = ob_get_level();
        $tmpPath = $this->getTemporaryPath();
        try {
            file_put_contents($tmpPath, $template);

            ob_start();
            $this->renderFile($tmpPath);
            $results = ob_get_clean();

            return $this->renderCache[$key] = ltrim($results);
        } catch (Throwable) {
            if ($componentState->componentPath != '') {
                $this->disabledComponents[$componentState->componentPath] = true;
            }

            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw new CompilerRenderException;
        } finally {
            @unlink($tmpPath);
        }
    }
}
