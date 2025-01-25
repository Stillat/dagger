<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\Dagger\Compiler\ExtractedSlot;

trait CompilesSlots
{
    protected function getSlotName(ComponentNode $component): string
    {
        return Str::after($component->name, 'slot:');
    }

    protected function buildForwardedSlotPath(string $name): string
    {
        $parts = explode('.', $name);
        $slotName = array_pop($parts);

        $prefix = collect($parts)->map(fn ($id) => '#'.$id)->join('');

        return "{$prefix}.{$slotName}";
    }

    protected function forwardSlots(string $forwardingVarName): string
    {
        $output = '';
        $componentPath = $this->getForwardedComponentPath();

        if (! isset($this->forwardedSlots[$componentPath])) {
            return $output;
        }

        if ($this->activeComponent->shouldCache) {
            $this->activeComponent->getVariableForwardingVariable();
        }

        foreach ($this->forwardedSlots[$componentPath]['slots'] as $slotName => $slotDetails) {

            [$valueContainer, $rawParams, $compiledParams] = $slotDetails;
            $slotPath = "{$componentPath}.{$slotName}";

            foreach ($rawParams as $paramName => $paramValue) {
                $this->activeComponent->addNamedSlotHoistedVar($paramName, $paramValue);
            }

            if ($this->activeComponent->shouldCache) {
                $replacementKey = $this->replacementManager
                    ->getReplacement($this->activeComponent, "scope.forwarded::{$slotPath}");

                $slotTemplate = <<<'PHP'
<?php
/** @var \Stillat\Dagger\Runtime\SlotContainer $slotVarName */
$slotVarName->setForwardedSlot($slotName, new \Illuminate\View\ComponentSlot('$replacementKey', $compiledParams));
?>
PHP;

                $slotTemplate = Str::swap([
                    '$replacementKey' => $replacementKey,
                    '$compiledParams' => $compiledParams,
                ], $slotTemplate);

                $this->activeComponent->cacheReplacements[$replacementKey] = Str::swap([
                    'valueContainer' => $valueContainer,
                    '$slotPath' => "'{$slotPath}'",
                ], '<?php echo $valueContainer->getForwardedSlot($slotPath); ?>');
            } else {
                $slotTemplate = <<<'PHP'
<?php
/** @var \Stillat\Dagger\Runtime\SlotContainer $slotVarName */
$slotVarName->setForwardedSlot($slotName, $valueContainer->getForwardedSlot($slotPath));
?>
PHP;
            }

            $output .= Str::swap([
                'slotVarName' => $forwardingVarName,
                'valueContainer' => $valueContainer,
                '$slotName' => "'{$slotName}'",
                '$slotPath' => "'{$slotPath}'",
            ], $slotTemplate);
        }

        return $output;
    }

    protected function compileForwardedSlots(array $slotsToForward): string
    {
        if (count($slotsToForward) === 0) {
            return '';
        }

        $output = '';
        $forwardingVarName = $this->activeComponent->getVariableForwardingVariable();

        foreach ($slotsToForward as $extractedSlot) {
            $childSlotPath = $this->buildForwardedSlotPath($extractedSlot->getName());
            $parentSlotPath = Str::beforeLast($childSlotPath, '.');
            $childSlotName = Str::after($childSlotPath, '.');

            if (! isset($this->forwardedSlots[$parentSlotPath])) {
                $this->forwardedSlots[$parentSlotPath] = [
                    'slots' => [],
                ];
            }

            $this->forwardedSlotPaths[$childSlotPath] = $forwardingVarName;

            $compiledAttributes = $this->attributeCompiler->compile([]);

            if (count($extractedSlot->node->parameters) > 0) {
                $forwardedParams = $this->makeForwardedParameters($extractedSlot);
                $compiledAttributes = $this->attributeCompiler->compile($forwardedParams);

                $slotTemplate = <<<'PHP'
<?php
/** @var \Stillat\Dagger\Runtime\Forwarding\ValueContainer $valueContainer */
$valueContainer->addForwardedSlot($slotPath, function () use ($__currentScope, $valueContainer, $componentVar) {
    ob_start(); extract($__currentScope); $__compiledSlotAttributesVarSuffix = $compiledParams; ?>#inner#<?php
    $slotContent = ob_get_clean();

    return [$slotContent, $__compiledSlotAttributesVarSuffix];
});
?>
PHP;

                $slotTemplate = Str::swap([
                    '$compiledParams' => $compiledAttributes,
                ], $slotTemplate);
            } else {
                $slotTemplate = <<<'PHP'
<?php
/** @var \Stillat\Dagger\Runtime\Forwarding\ValueContainer $valueContainer */
$valueContainer->addForwardedSlot($slotPath, function () use ($__currentScope, $valueContainer, $componentVar) {
    ob_start(); extract($__currentScope) ?>#inner#<?php
    $slotContent = ob_get_clean();

    return [$slotContent, []];
});
?>
PHP;
            }

            $this->forwardedSlots[$parentSlotPath]['slots'][$childSlotName] = [
                $forwardingVarName,
                $this->attributeCompiler->getLastCompiledValues(),
                $compiledAttributes,
            ];

            $innerSlotContent = trim($extractedSlot->node->innerDocumentContent);
            if (mb_strlen($innerSlotContent) > 0) {
                $innerSlotContent = $this->storeComponentBlock(
                    $this->compiler->compileString($innerSlotContent)
                );
            }

            $output .= Str::swap([
                'valueContainer' => $forwardingVarName,
                '$slotPath' => "'{$childSlotPath}'",
                '#inner#' => $innerSlotContent,
                '$componentVar' => '$'.$this->activeComponent->getVariableName(),
                '__currentScope' => $this->activeComponent->getGlobalScopeVariableName(),
            ], $slotTemplate);
        }

        return $output;
    }

    /**
     * @return ParameterNode[]
     */
    protected function makeForwardedParameters(ExtractedSlot $slot): array
    {
        $valueContainer = $this->activeComponent->getVariableForwardingVariable();
        $newParams = [];
        foreach ($slot->node->parameters as $param) {
            $cachePath = $this->activeComponent->node->name.'.slot.'.$slot->getName().$param->materializedName;
            $forwardedVarName = $this->activeComponent->makeForwardedVariableName($cachePath);

            if ($param->type == ParameterType::Parameter) {
                $this->activeComponent->addNamedSlotHoistedVar($param->materializedName, $param->value, $cachePath);
            } elseif ($param->type == ParameterType::ShorthandDynamicVariable) {
                $this->activeComponent->addForwardedVariable($forwardedVarName, $param->value);
            } elseif ($param->type == ParameterType::DynamicVariable) {
                $this->activeComponent->addForwardedVariable($forwardedVarName, $param->value);
            }

            $newParams[] = $this->compileForwardedParameter(
                $param,
                $param->materializedName,
                $valueContainer,
                $forwardedVarName
            );
        }

        return $newParams;
    }

    protected function compileNamedSlots(array $namedSlots, string $variableName): string
    {
        if (empty($namedSlots)) {
            return '';
        }

        $slotContents = '';

        /** @var ExtractedSlot $slot */
        foreach ($namedSlots as $slot) {
            if (! empty($slot->node->parameters)) {
                $slot->node->parameters = $this->makeForwardedParameters($slot);
            }

            $fullSlotPath = $this->activeComponent->compilerId.'.'.$slot->getName();
            if (isset($this->forwardedSlotPaths[$fullSlotPath])) {
                unset($this->forwardedSlotPaths[$fullSlotPath]);

                continue;
            }

            $innerContent = trim($slot->node->innerDocumentContent);
            if (mb_strlen($innerContent) > 0) {
                $innerContent = $this->storeComponentBlock(
                    $this->compiler->compileString($innerContent)
                );
            }

            $hasParams = ! empty($slot->node->parameters);
            $compiledAttributes = $hasParams
                ? $this->attributeCompiler->compile($slot->node->parameters)
                : null;

            if ($this->activeComponent->shouldCache) {
                $cachePath = $this->activeComponent->node->name.'.slot.'.$slot->getName();
                $replacementKey = $this->replacementManager->getReplacement(
                    $this->activeComponent,
                    'scope.named::'.$cachePath
                );

                if ($hasParams) {
                    $cachedSlotStub = <<<'PHP'
<?php $slotVarName->setSlotContent($slotName, '$replacement', $compiledAttributes); ?>
PHP;
                    $cachedSlot = Str::swap([
                        '$compiledAttributes' => $compiledAttributes,
                    ], $cachedSlotStub);
                } else {
                    $cachedSlot = <<<'PHP'
<?php $slotVarName->setSlotContent($slotName, '$replacement'); ?>
PHP;
                }

                $this->activeComponent->cacheReplacements[$replacementKey] = $innerContent;

                return Str::swap([
                    '$slotName' => "'{$slot->getName()}'",
                    '$replacement' => $replacementKey,
                    'slotVarName' => $variableName,
                ], $cachedSlot);
            }

            if ($hasParams) {
                $uncachedSlotTemplate = <<<'PHP'
<?php ob_start(); ?>#inner#<?php $slotVarName->setSlotContent($slotName, ob_get_clean(), $compiledAttributes); ?>
PHP;
                $uncachedSlotTemplate = Str::swap([
                    '$compiledAttributes' => $compiledAttributes,
                ], $uncachedSlotTemplate);
            } else {
                $uncachedSlotTemplate = <<<'PHP'
<?php ob_start(); ?>#inner#<?php $slotVarName->setSlotContent($slotName, ob_get_clean()); ?>
PHP;
            }

            $slotContents .= Str::swap([
                '$slotName' => "'{$slot->getName()}'",
                '#inner#' => $innerContent,
                'slotVarName' => $variableName,
            ], $uncachedSlotTemplate);
        }

        return $slotContents;
    }

    protected function compileSlotContent(string $template, string $variableName): string
    {
        if (mb_strlen(trim($template)) === 0) {
            return '';
        }

        $tmpSlotPath = $this->activeComponent->compilerId.'.default';

        if (isset($this->forwardedSlotPaths[$tmpSlotPath])) {
            $varContainer = $this->forwardedSlotPaths[$tmpSlotPath];
            unset($this->forwardedSlotPaths[$tmpSlotPath]);

            $slotContent = <<<'PHP'
<?php $slotVarName->setDefaultContent($varContainer->getForwardedSlot($slotPath)); ?>
PHP;

            return Str::swap([
                'slotVarName' => $variableName,
                'varContainer' => $varContainer,
                '$slotPath' => "'{$tmpSlotPath}'",
            ], $slotContent);
        }

        $inner = $this->storeComponentBlock($this->compiler->compileString($template));

        if ($this->activeComponent->shouldCache === true) {
            $replacementString = $this->replacementManager->getReplacement($this->activeComponent, 'scope.default');

            $cachedSlot = <<<'PHP'
<?php
    \Stillat\Dagger\Facades\ComponentEnv::componentCache()->put($__cacheKey, $componentVarName);
    $slotVarName->setDefaultContent('$replacement');
?>
PHP;

            $this->activeComponent->cacheReplacements[$replacementString] = $inner;

            return Str::swap([
                '#inner#' => $inner,
                'componentVarName' => $this->activeComponent->getVariableName(),
                '$replacement' => $replacementString,
                'slotVarName' => $variableName,
            ], $cachedSlot);
        }

        $slotContent = <<<'PHP'
<?php ob_start(); ?>#inner#<?php $slotVarName->setDefaultContent(ob_get_clean()); ?>
PHP;

        return Str::swap([
            '#inner#' => $inner,
            'slotVarName' => $variableName,
        ], $slotContent);
    }
}
