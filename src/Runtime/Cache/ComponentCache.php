<?php

namespace Stillat\Dagger\Runtime\Cache;

class ComponentCache
{
    protected array $instances = [];

    public function put(string $key, $component)
    {
        $this->instances[$key] = clone $component;
    }

    public function get(string $key)
    {
        return $this->instances[$key] ?? null;
    }
}
