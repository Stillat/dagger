<?php

namespace Stillat\Dagger\Tests\Mixins;

use Stillat\Dagger\Runtime\Component;

class MixinThree
{
    protected ?Component $component = null;

    public function withComponent(Component $component)
    {
        $this->component = $component;
    }

    public function testMethod(string $suffix): string
    {
        return ($this->component?->name ?? '').'::'.$suffix;
    }
}
