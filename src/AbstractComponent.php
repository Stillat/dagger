<?php

namespace Stillat\Dagger;

use Illuminate\Support\Fluent;
use Illuminate\View\ComponentAttributeBag;
use Stillat\Dagger\Exceptions\RuntimeException;
use Stillat\Dagger\Parser\ComponentTap;
use Stillat\Dagger\Runtime\SlotContainer;

abstract class AbstractComponent
{
    public readonly Fluent $data;

    public readonly string $name;

    public readonly ComponentAttributeBag $attributes;

    public readonly SlotContainer $slots;

    public readonly ?AbstractComponent $parent;

    public readonly int $depth;

    protected array $parentCache = [];

    public function __construct(
        ?ComponentTap $tap = null,
        ?string $name = '',
        ?ComponentAttributeBag $attributes = null,
        ?Fluent $data = null,
        ?SlotContainer $slots = null,
        ?AbstractComponent $parent = null,
        ?int $depth = null
    ) {
        if ($tap) {
            $tap->component = $this;
        }

        $this->depth = $depth ?? 0;
        $this->name = $name ?? 'component';
        $this->attributes = $attributes ?? new ComponentAttributeBag;
        $this->data = $data ?? new Fluent;
        $this->slots = $slots ?? new SlotContainer;
        $this->parent = $parent;
    }

    public function parent(?string $name = null): ?AbstractComponent
    {
        if ($name === null) {
            return $this->parent;
        }

        if (isset($this->parentCache[$name])) {
            return $this->parentCache[$name];
        }

        $parent = $this->parent;
        $parentToReturn = null;

        while ($parent != null) {
            if ($parent->name === $name) {
                $parentToReturn = $parent;
                break;
            }

            $parent = $parent->parent;
        }

        return $this->parentCache[$name] = $parentToReturn;
    }

    public function isRoot(): bool
    {
        return $this->data == 0;
    }

    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    public function hasAny(array $key): bool
    {
        return $this->data->hasAny($key);
    }

    abstract public function mixin(string|array $classes): static;

    abstract public function props(string|array $props): static;

    abstract public function validateProps(string|array $props, string|array $messages = []): static;

    abstract public function aware(string|array $aware): static;

    abstract public function trimOutput(): static;

    abstract public function cache(): static;

    /**
     * @throws RuntimeException
     */
    public function compiler(?bool $allowOptimizations = null): static
    {
        throw new RuntimeException('Cannot call compiler method at runtime.');
    }

    public function __get(string $name)
    {
        return $this->data->get($name);
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
