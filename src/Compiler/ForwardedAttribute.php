<?php

namespace Stillat\Dagger\Compiler;

use Stillat\BladeParser\Nodes\Components\ParameterNode;

class ForwardedAttribute
{
    public function __construct(
        public readonly ?string $attribute,
        public readonly string $forwardedVarName,
        public readonly string $forwardingVariableName,
        public readonly ParameterNode $parameter
    ) {}
}
