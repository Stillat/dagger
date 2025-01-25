<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\Dagger\Compiler\ForwardedAttribute;

trait CompilesForwardedAttributes
{
    protected function filterParameters(ComponentNode $node): array
    {
        $compilerParams = [];
        $paramsToKeep = [];

        foreach ($node->parameters as $param) {
            if (Str::startsWith($param->name, '##')) {
                $param->name = mb_substr($param->name, 1);
                $paramsToKeep[] = $param;

                continue;
            }

            if (Str::startsWith($param->name, ['#', ':#']) && in_array($param->materializedName, $this->compilerDirectiveParams)) {
                $compilerParams[] = $param;
            } else {
                $paramsToKeep[] = $param;
            }
        }

        $node->parameters = $paramsToKeep;
        $node->parameterCount = count($paramsToKeep);

        return $compilerParams;
    }

    public function addForwardedProperty(string $path, $value): void
    {
        if (Str::contains($path, '.')) {
            $path = implode('#', explode('.', $path));
        }

        if (! isset($this->forwardedProperties[$path])) {
            $this->forwardedProperties[$path] = [];
        }

        $this->forwardedProperties[$path][] = $value;
    }

    protected function isForwardedParameter(ParameterNode $param): bool
    {
        if (str_starts_with($param->name, ':#')) {
            return true;
        }

        if (str_starts_with($param->materializedName, '#') && ! str_starts_with($param->materializedName, '##')) {
            if (in_array($param->name, $this->compilerDirectiveParams)) {
                return false;
            }

            return true;
        }

        return false;
    }

    protected function filterComponentParams(array $params): array
    {
        return collect($params)
            ->reject(fn ($param) => $this->isForwardedParameter($param))
            ->all();
    }

    protected function compileForwardedAttributes(ComponentNode $node): void
    {
        $componentPath = $this->getForwardedComponentPath();

        foreach ($node->parameters as $parameter) {
            if (! $this->isForwardedParameter($parameter)) {
                continue;
            }

            $nameToUse = $parameter->name;

            if (str_starts_with($nameToUse, ':')) {
                $nameToUse = mb_substr($nameToUse, 1);
            }

            $path = $nameToUse;
            $attribute = null;

            if (Str::contains($nameToUse, ':')) {
                [$path, $attribute] = explode(':', $nameToUse, 2);
            }

            $forwardedVarName = $this->activeComponent->makeForwardedVariableName();

            if ($parameter->type == ParameterType::DynamicVariable) {
                $this->activeComponent->addForwardedVariable($forwardedVarName, $parameter->value);
            }

            $this->addForwardedProperty($path, new ForwardedAttribute(
                $attribute,
                $forwardedVarName,
                $this->activeComponent->getVariableForwardingVariable(),
                $parameter
            ));
        }

        if (isset($this->forwardedProperties[$componentPath])) {
            /** @var ForwardedAttribute $forwardedDetails */
            foreach ($this->forwardedProperties[$componentPath] as $forwardedDetails) {
                $node->parameters[] = $this->compileForwardedParameter(
                    $forwardedDetails->parameter,
                    $forwardedDetails->attribute,
                    $forwardedDetails->forwardingVariableName,
                    $forwardedDetails->forwardedVarName
                );
            }

            unset($this->forwardedProperties[$componentPath]);
        }
    }

    protected function compileForwardedParameter(
        ParameterNode $param,
        string $paramName,
        string $valueContainer,
        string $valueName
    ): ParameterNode {
        $newParam = new ParameterNode;
        $newParam->name = $newParam->materializedName = $paramName;

        if ($param->type == ParameterType::Attribute) {
            $newParam->type = ParameterType::Attribute;
        } elseif ($param->type == ParameterType::Parameter) {
            // Can just pass the original value.
            $newParam->type = ParameterType::Parameter;
            $newParam->value = $param->value;
            $newParam->valueNode = $param->valueNode;
        } elseif ($param->type == ParameterType::DynamicVariable) {
            $newParam->type = ParameterType::DynamicVariable;
            $newParam->value = "\${$valueContainer}->getForwardedValue('{$valueName}')";
        } elseif ($param->type == ParameterType::ShorthandDynamicVariable) {
            $newParam->type = ParameterType::DynamicVariable;
            $newParam->value = "\${$valueContainer}->getForwardedValue('{$valueName}')";
        }

        return $newParam;
    }
}
