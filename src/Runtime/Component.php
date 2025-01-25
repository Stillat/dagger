<?php

namespace Stillat\Dagger\Runtime;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use Stillat\Dagger\AbstractComponent;
use Stillat\Dagger\Exceptions\RuntimeException;

class Component extends AbstractComponent
{
    protected array $macros = [];

    protected static array $methodCache = [];

    /**
     * @throws RuntimeException
     */
    public function props(array|string $props): static
    {
        throw new RuntimeException('Cannot call props method at runtime.');
    }

    /**
     * @throws RuntimeException
     */
    public function validateProps(array|string $props, array|string $messages = []): static
    {
        throw new RuntimeException('Cannot call validateProps method at runtime.');
    }

    /**
     * @throws RuntimeException
     */
    public function aware(array|string $aware): static
    {
        throw new RuntimeException('Cannot call aware method at runtime.');
    }

    /**
     * @throws RuntimeException
     */
    public function trimOutput(): static
    {
        throw new RuntimeException('Cannot call trimOutput method at runtime.');
    }

    /**
     * @throws RuntimeException
     */
    public function cache(): static
    {
        throw new RuntimeException('Cannot call cache method at runtime.');
    }

    protected static function getMethods($instance): array
    {
        $className = get_class($instance);

        if (isset(static::$methodCache[$className])) {
            return self::$methodCache[$className];
        }

        $methods = (new ReflectionClass($instance))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        return self::$methodCache[$className] = $methods;
    }

    public function mixin(array|string $classes): static
    {
        if (is_string($classes)) {
            $classes = [$classes];
        }

        $mixinData = [];

        foreach ($classes as $className) {
            $instance = app($className);
            $methods = self::getMethods($instance);

            $withComponentMethod = collect($methods)->where(fn (ReflectionMethod $method) => $method->name === 'withComponent')->first();
            $dataMethod = collect($methods)->where(fn (ReflectionMethod $method) => $method->name === 'data')->first();

            // Ensure we set the component instance first if we need to.
            if ($withComponentMethod != null) {
                $instance->withComponent($this);
            }

            foreach ($methods as $method) {
                if ($method->name === 'data' || $method->name === 'withComponent') {
                    continue;
                }

                $this->macros[$method->name] = function (...$args) use ($instance, $method) {
                    return $method->invoke($instance, ...$args);
                };
            }

            // Merge the data in last. If a mixin also receives a component, they may call mixin methods on it.
            if ($dataMethod != null) {
                $data = $instance->data();

                if (! is_array($data)) {
                    continue;
                }

                $mixinData = array_merge($mixinData, $data);
            }
        }

        foreach ($mixinData as $key => $value) {
            if (isset($this->data[$key])) {
                continue;
            }

            $this->data[$key] = $value;
        }

        return $this;
    }

    public function getMacros(): array
    {
        return $this->macros;
    }

    public function __call($method, $parameters)
    {
        if (! isset($this->macros[$method])) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = $this->macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}
