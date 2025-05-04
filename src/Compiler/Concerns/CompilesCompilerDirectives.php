<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Position;

trait CompilesCompilerDirectives
{
    private function compileForwardedProperties(): string
    {
        $forwardedProps = [];
        $forwardedVars = $this->activeComponent->getForwardedVariables();
        $forwardedVar = $this->activeComponent->getVariableForwardingVariable();

        foreach ($forwardedVars as $forwardedName => $forwardedValue) {
            $forwardedProps[] =
                "\${$forwardedVar}->addForwardedValue('{$forwardedName}', {$forwardedValue});";
        }

        return implode($this->newlineStyle, $forwardedProps);
    }

    protected function compileComponentEnd(ComponentNode $node, string $viewPath): string
    {
        if ($node->isClosedBy != null) {
            return $this->compileComponentEnd($node->isClosedBy, $viewPath);
        }

        if ($node->position == null) {
            return '';
        }

        return $this->compileComponentPosition($node->position, $viewPath);
    }

    protected function compileComponentStart(ComponentNode $node, string $viewPath): string
    {
        if ($node->position == null) {
            return '';
        }

        return $this->compileComponentPosition($node->position, $viewPath);
    }

    protected function compileComponentPosition(Position $position, string $viewPath): string
    {
        $stub = <<<'PHP'
/**
FILE: {file}
LINE: {line}
*/
PHP;

        return Str::swap([
            '{file}' => $viewPath,
            '{line}' => $position->startLine,
            '{char}' => $position->startColumn,
        ], $stub);
    }

    protected function compileCompilerDirectives(string $template): string
    {
        $lines = explode($this->newlineStyle, $template);
        $newLines = [];

        foreach ($lines as $line) {
            if ($this->processCompileIfVar($line, $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:INJECTED_PROPS', fn () => $this->compileInjectedProps(), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:PROPS_DEFAULT', fn () => $this->compileDefaultProps(), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:AWARE', fn () => $this->compileAware(), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:PROP_VALIDATION', fn () => $this->compilePropValidation(), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:EXTRACT_PROP_VALUES_FROM_PARAMS', fn () => $this->compileExtractPropsFromParams(), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:INJECTIONS', fn () => $this->compileComponentMixins(), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:VAR_CLEANUP', fn () => $this->compileVariableCleanup($this->activeComponent->getCleanupVariables()), $newLines)) {
                continue;
            }
            if ($this->processCompilePlaceholder($line, '/** COMPILE:OUTPUT', fn () => $this->compileComponentOutput(), $newLines)) {
                continue;
            }
            if ($this->processCompileLocStart($line, $newLines)) {
                continue;
            }
            if ($this->processCompileLocEnd($line, $newLines)) {
                continue;
            }
            if ($this->processCompileCacheRetrieval($line, $newLines)) {
                continue;
            }
            if ($this->processCompileComponentStart($line, $newLines)) {
                continue;
            }
            if ($this->processCompileComponentEnd($line, $newLines)) {
                continue;
            }
            if ($this->processCompileComponentResult($line, $newLines)) {
                continue;
            }

            $newLines[] = $line;
        }

        return implode($this->newlineStyle, $newLines);
    }

    protected function processCompileIfVar(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE_IF_VAR:')) {
            return false;
        }

        $trimmedLine = str($line)
            ->trim()
            ->after(':');

        $varToCheck = (string) $trimmedLine
            ->before(' ')
            ->trim();

        $remainder = $trimmedLine
            ->after(' ')
            ->trim();

        $remainder = (string) str($remainder)
            ->substr(0, str($remainder)->length() - 2)
            ->trim();

        if (! $this->activeComponent->hasCreatedDynamicVariable($varToCheck)) {
            return true;
        }

        if (trim($remainder) === '#forwardedProperties') {
            $newLines[] = $this->compileForwardedProperties();

            return true;
        }

        $dynamicVarName = '$'.$this->activeComponent->getDynamicVariable($varToCheck);
        $newLines[] = str_replace('$targetVar', $dynamicVarName, $remainder);

        return true;
    }

    protected function processCompilePlaceholder(string $line, string $prefix, callable $callback, array &$newLines): bool
    {
        if (! str_starts_with($line, $prefix)) {
            return false;
        }

        $newLines[] = $callback();

        return true;
    }

    protected function processCompileLocStart(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE:LOC_START')) {
            return false;
        }

        $newLines[] = $this->compileComponentStart(
            $this->activeComponent->node,
            $this->activeComponent->viewPath,
        );

        return true;
    }

    protected function processCompileLocEnd(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE:LOC_END')) {
            return false;
        }

        $newLines[] = $this->compileComponentEnd(
            $this->activeComponent->node,
            $this->activeComponent->viewPath,
        );

        return true;
    }

    protected function processCompileCacheRetrieval(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE:CACHE_RETRIEVAL')) {
            return false;
        }

        $newLines[] = '$__result = \Stillat\Dagger\Facades\ComponentEnv::cache()->get($__cacheKey);';
        $newLines[] = $this->compileComponentOutput();
        $newLines[] = 'unset($__result);';

        return true;
    }

    protected function processCompileComponentStart(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE:COMPONENT_START')) {
            return false;
        }

        if (! $this->activeComponent->shouldCache) {
            return true;
        }

        $cacheStart = <<<'PHP'
$__cacheEnabledVarSuffix = true;
$__cacheKey = \Stillat\Dagger\Runtime\Cache\AttributeCache::getAttributeCacheKey($__componentData);

if ($__cacheKey !== null) { $__cacheKey = '__cacheKeyPrefix'.$__cacheKey; } else { $__cacheEnabledVarSuffix = false; }

if ($__cacheEnabledVarSuffix && \Stillat\Dagger\Facades\ComponentEnv::cache()->has($__cacheKey)) {
if (isset($componentVarName)) { $__componentOriginalVarSuffix = $componentVarName; }
$componentVarName = \Stillat\Dagger\Facades\ComponentEnv::componentCache()->get($__cacheKey);
/** COMPILE:CACHE_RETRIEVAL */
unset($componentVarName);
if (isset($__componentOriginalVarSuffix)) { $componentVarName = $__componentOriginalVarSuffix; unset($__componentOriginalVarSuffix); }
} else {
PHP;

        if ($this->activeComponent->hasForwardingContainer()) {
            $hoistedSlotVars = $this->activeComponent->getHoistedSlotVariables();
            $hoistedVar = '';

            if (count($hoistedSlotVars) > 0) {
                $hoistedVar = ', '.$this->compilePhpArray($hoistedSlotVars);
            }

            $cacheStart = str_replace(
                '$__componentData',
                'array_merge($__componentData, $'.$this->activeComponent->getVariableForwardingVariable().'->values()'.$hoistedVar.')',
                $cacheStart
            );
        }

        $newLines[] = $this->compileCompilerDirectives($cacheStart);

        return true;
    }

    protected function processCompileComponentEnd(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE:COMPONENT_END')) {
            return false;
        }

        // If caching is enabled, close the `if { ... } else { ... }` block started in COMPONENT_START.
        if ($this->activeComponent->shouldCache) {
            $newLines[] = '}';
        }

        return true;
    }

    protected function processCompileComponentResult(string $line, array &$newLines): bool
    {
        if (! str_starts_with($line, '/** COMPILE:COMPONENT_RESULT')) {
            return false;
        }

        if ($this->activeComponent->shouldCache) {
            $newLines[] = '\Stillat\Dagger\Facades\ComponentEnv::cache()->put($__cacheKey, $__result);';
        }

        return true;
    }
}
