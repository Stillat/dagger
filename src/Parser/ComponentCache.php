<?php

namespace Stillat\Dagger\Parser;

use Stillat\Dagger\Compiler\ComponentState;

class ComponentCache
{
    protected array $items = [];

    public function get(string $key): ?ComponentState
    {
        if (! isset($this->items[$key])) {
            return null;
        }

        return clone $this->items[$key];
    }

    public function put(string $key, ComponentState $componentState): void
    {
        $this->items[$key] = $componentState;
    }

    public function clear(): void
    {
        $this->items = [];
    }
}
