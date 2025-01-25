<?php

namespace Stillat\Dagger\Runtime\Forwarding;

use Illuminate\View\ComponentSlot;

class ValueContainer
{
    protected array $forwardedSlots = [];

    protected array $forwardedValues = [];

    public function addForwardedValue(string $path, mixed $value): void
    {
        $this->forwardedValues[$path] = $value;
    }

    public function getForwardedValue(string $path): mixed
    {
        if (! isset($this->forwardedValues[$path])) {
            return null;
        }

        $value = $this->forwardedValues[$path];

        unset($this->forwardedValues[$path]);

        return $value;
    }

    public function addForwardedSlot(string $path, $callback): void
    {
        $tmpValue = $callback();

        $this->forwardedSlots[$path] = new ComponentSlot($tmpValue[0], $tmpValue[1]);
    }

    public function getForwardedSlot(string $path): ComponentSlot
    {
        return $this->forwardedSlots[$path];
    }

    public function values(): array
    {
        return $this->forwardedValues;
    }

    public function __destruct()
    {
        $this->forwardedSlots = [];
        $this->forwardedValues = [];
    }
}
