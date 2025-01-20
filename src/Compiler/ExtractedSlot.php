<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;

class ExtractedSlot
{
    public function __construct(
        public ComponentNode $node
    ) {}

    public function isForwardedSlot(): bool
    {
        return Str::contains($this->getName(), '.');
    }

    public function getName(): string
    {
        return Str::after($this->node->name, ':');
    }
}
