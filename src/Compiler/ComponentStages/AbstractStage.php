<?php

namespace Stillat\Dagger\Compiler\ComponentStages;

use PhpParser\NodeTraverser;

abstract class AbstractStage
{
    protected NodeTraverser $traverser;

    public function __construct()
    {
        $this->traverser = new NodeTraverser;
    }
}
