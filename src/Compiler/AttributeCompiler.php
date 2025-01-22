<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;

class AttributeCompiler
{
    protected string $escapedParameterPrefix = '';

    protected array $compiledParamNames = [];

    protected array $compiledValues = [];

    protected function arrayValue(string $value, bool $isString = true): string
    {
        if ($isString) {
            $value = str_replace('\'', '\\\'', $value);
            $value = "'{$value}'";
        }

        return $value;
    }

    protected function toArraySyntax(string $name, string $value): string
    {
        return "'{$name}'=>{$value}";
    }

    protected function compileAttributeEchos(string $attributeString): string
    {
        $value = Blade::compileEchos($attributeString);

        $value = $this->escapeSingleQuotesOutsideOfPhpBlocks($value);

        $value = str_replace('<?php echo ', '\'.', $value);

        return str_replace('; ?>', '.\'', $value);
    }

    protected function escapeSingleQuotesOutsideOfPhpBlocks(string $value): string
    {
        return collect(token_get_all($value))->map(function ($token) {
            if (! is_array($token)) {
                return $token;
            }

            return $token[0] === T_INLINE_HTML
                ? str_replace("'", "\\'", $token[1])
                : $token[1];
        })->implode('');
    }

    public function compile(array $parameters, array $propNames = []): string
    {
        return '['.implode(',', $this->toCompiledArray($parameters, $propNames)).']';
    }

    public function getLastCompiledNames(): array
    {
        return $this->compiledParamNames;
    }

    public function getLastCompiledValues(): array
    {
        return $this->compiledValues;
    }

    public function compileValue(ParameterNode $parameter): string
    {
        if ($parameter->type == ParameterType::Parameter) {
            return $this->arrayValue($parameter->value);
        } elseif ($parameter->type == ParameterType::DynamicVariable) {
            return $this->arrayValue($parameter->value, false);
        } elseif ($parameter->type == ParameterType::ShorthandDynamicVariable) {
            return $this->arrayValue($parameter->value, false);
        } elseif ($parameter->type == ParameterType::EscapedParameter) {
            return $this->arrayValue($parameter->value);
        } elseif ($parameter->type == ParameterType::Attribute) {
            return $this->arrayValue('true', false);
        } elseif ($parameter->type == ParameterType::InterpolatedValue) {
            return $this->arrayValue("'".$this->compileAttributeEchos($parameter->value)."'", false);
        }

        return $parameter->valueNode->content;
    }

    /**
     * @param  ParameterNode[]  $parameters
     */
    public function toCompiledArray(array $parameters, array $propNames = []): array
    {
        $this->compiledValues = $this->compiledParamNames = [];

        if (count($parameters) === 0) {
            return [];
        }

        $compiledParameters = [];

        foreach ($parameters as $parameter) {
            $paramName = $paramValue = null;

            if ($parameter->type == ParameterType::Parameter) {
                $paramName = in_array($parameter->name, $propNames) ? Str::camel($parameter->name) : $parameter->name;
                $paramValue = $this->arrayValue($parameter->value);
                $compiledParameters[] = $this->toArraySyntax($paramName, $paramValue);
            } elseif ($parameter->type == ParameterType::DynamicVariable) {
                $paramName = Str::camel($parameter->materializedName);
                $paramValue = $this->arrayValue($parameter->value, false);
                $compiledParameters[] = $this->toArraySyntax($paramName, $paramValue);
            } elseif ($parameter->type == ParameterType::ShorthandDynamicVariable) {
                $paramName = Str::camel($parameter->materializedName);
                $paramValue = $this->arrayValue($parameter->value, false);
                $compiledParameters[] = $this->toArraySyntax($paramName, $paramValue);
            } elseif ($parameter->type == ParameterType::EscapedParameter) {
                $paramName = $this->escapedParameterPrefix.$parameter->materializedName;
                $paramValue = $this->arrayValue($parameter->value);
                $compiledParameters[] = $this->toArraySyntax($paramName, $paramValue);
            } elseif ($parameter->type == ParameterType::Attribute) {
                $paramName = $parameter->materializedName;
                $paramValue = $this->arrayValue('true', false);
                $compiledParameters[] = $this->toArraySyntax($paramName, $paramValue);
            } elseif ($parameter->type == ParameterType::InterpolatedValue) {
                $paramName = $parameter->materializedName;
                $paramValue = $this->arrayValue("'".$this->compileAttributeEchos($parameter->value)."'", false);
                $compiledParameters[] = $this->toArraySyntax($paramName, $paramValue);
            }

            if ($paramName) {
                $this->compiledValues[$paramName] = $paramValue;
            }
        }

        $this->compiledParamNames = array_keys($this->compiledValues);

        return $compiledParameters;
    }
}
