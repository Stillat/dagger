<?php

namespace Stillat\Dagger;

use Stillat\Dagger\Facades\ComponentEnv;
use Stillat\Dagger\Parser\Component;
use Stillat\Dagger\Parser\ComponentTap;

function component(?ComponentTap $tap = null): AbstractComponent
{
    return new Component($tap);
}

function render($mixed): string
{
    return $mixed;
}

function _parent(): ?AbstractComponent
{
    return ComponentEnv::activeComponent()?->parent;
}

function current(): ?AbstractComponent
{
    return ComponentEnv::activeComponent();
}
