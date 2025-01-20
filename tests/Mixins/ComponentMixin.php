<?php

namespace Stillat\Dagger\Tests\Mixins;

use Illuminate\Support\Str;
use Stillat\Dagger\Runtime\Component;

class ComponentMixin
{
    protected ?Component $component = null;

    public function data(): array
    {
        return [
            'name_upper' => Str::upper($this->component->name),
        ];
    }

    public function withComponent(Component $component): void
    {
        $this->component = $component;
    }
}
