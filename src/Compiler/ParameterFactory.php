<?php

namespace Stillat\Dagger\Compiler;

use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;

class ParameterFactory
{
    public static function makeVariableReference(string $variableName, string $value): ParameterNode
    {
        $param = new ParameterNode;
        $param->type = ParameterType::DynamicVariable;
        $param->value = $value;
        $param->name = $param->materializedName = $variableName;

        return $param;
    }
}
