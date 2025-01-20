<?php

namespace Stillat\Dagger\Runtime;

use Illuminate\Support\Fluent;
use Illuminate\View\Component as IlluminateComponent;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\Factory;
use Stillat\Dagger\AbstractComponent;
use Stillat\Dagger\Runtime\Cache\AttributeCache;
use Stillat\Dagger\Runtime\Cache\ComponentCache;

class ComponentEnvironment
{
    protected array $stack = [];

    protected readonly AttributeCache $cache;

    protected readonly ComponentCache $componentCache;

    protected ?Component $activeComponent = null;

    public function __construct()
    {
        $this->cache = new AttributeCache;
        $this->componentCache = new ComponentCache;
    }

    public function cache(): AttributeCache
    {
        return $this->cache;
    }

    public function componentCache(): ComponentCache
    {
        return $this->componentCache;
    }

    public function activeComponent(): Component
    {
        return $this->activeComponent;
    }

    public function push(Factory $viewFactory, Component $component): void
    {
        $this->stack[] = $component;

        $viewFactory->startComponent(
            EmptyHtmlable::instance(),
            $component->data->toArray()
        );

        $this->activeComponent = $component;
    }

    public function pushRaw(mixed $component): void
    {
        if ($component instanceof IlluminateComponent) {
            $component = $this->convertIlluminateComponent($component);
        }

        $this->stack[] = $component;

        $this->activeComponent = $component;
    }

    protected function convertIlluminateComponent(IlluminateComponent $component): AbstractComponent
    {
        $data = $component->data();
        $name = $component->componentName;
        $attributes = $data['attributes'] ?? new ComponentAttributeBag;
        unset($data['attributes']);

        return new Component(
            null,
            $name,
            $attributes,
            new Fluent($data),
            null,
            $this->last(),
            count($this->stack)
        );
    }

    public function depth(): int
    {
        return count($this->stack);
    }

    public function pop(?Factory $viewFactory): void
    {
        $viewFactory?->renderComponent();

        array_pop($this->stack);

        $this->activeComponent = $this->last();
    }

    public function last(): ?Component
    {
        if (count($this->stack) === 0) {
            return null;
        }

        return $this->stack[array_key_last($this->stack)];
    }
}
