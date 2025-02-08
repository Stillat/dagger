<?php

namespace Stillat\Dagger\Compiler;

use Stillat\BladeParser\Nodes\Components\ParameterFactory as BladeParameterFactory;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;

class ParameterFactory extends BladeParameterFactory
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
