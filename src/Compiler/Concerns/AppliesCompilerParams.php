<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Exceptions\InvalidCompilerParameterException;

trait AppliesCompilerParams
{
    protected array $invalidCompilerParamTypes = [
        ParameterType::DynamicVariable,
        ParameterType::ShorthandDynamicVariable,
        ParameterType::InterpolatedValue,
        ParameterType::AttributeEcho,
        ParameterType::AttributeRawEcho,
    ];

    protected function isValidCompilerParam(ParameterNode $param): bool
    {
        if ($this->isCacheParam($param)) {
            return true;
        }

        if (in_array($param->type, $this->invalidCompilerParamTypes, true)) {
            return false;
        }

        if (str_starts_with($param->value, '{') && str_ends_with($param->value, '}')) {
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidCompilerParameterException
     */
    protected function applyCompilerParameters(ComponentState $component, array $compilerParams): void
    {
        if (count($compilerParams) === 0) {
            return;
        }

        $cacheParam = collect($compilerParams)
            ->where(fn (ParameterNode $param) => $this->isCacheParam($param))
            ->first();

        if ($cacheParam) {
            $this->applyCacheParam($component, $cacheParam);
        }

        $compilerParams = collect($compilerParams)
            ->mapWithKeys(function (ParameterNode $param) {
                if (! $this->isValidCompilerParam($param)) {
                    throw new InvalidCompilerParameterException;
                }

                return [mb_substr($param->name, 1) => $param->value];
            })->all();

        if (isset($compilerParams['id'])) {
            $component->compilerId = '#'.$compilerParams['id'];
            $component->hasUserSuppliedId = true;
        }
    }
}
