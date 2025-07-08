<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Closure;
use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Exceptions\InvalidArgumentException;

trait ManagesComponentCompilerCallbacks
{
    protected array $componentCompilers = [];

    public function compileComponent(string $component, Closure $callback): static
    {
        $prefix = Str::before($component, ':');
        $component = Str::after($component, ':');

        if (! $prefix || ! in_array($prefix, $this->componentNamespaces)) {
            throw new InvalidArgumentException("[$component] must have a registered component prefix.");
        }

        if (! $component) {
            throw new InvalidArgumentException("[$component] is not a valid component name.");
        }

        return $this->compileComponentWithPrefix(
            $prefix,
            $component,
            $callback
        );
    }

    public function compileComponentWithPrefix(string $prefix, string $component, Closure $callback): static
    {
        if (! array_key_exists($prefix, $this->componentCompilers)) {
            $this->componentCompilers[$prefix] = [];
        }

        $this->componentCompilers[$prefix][$component] = $callback;

        return $this;
    }

    protected function compileComponentCallback(ComponentNode $node): mixed
    {
        if (! array_key_exists($node->componentPrefix, $this->componentCompilers)) {
            return false;
        }

        /**
         * @var $pattern
         * @var Closure $callback
         */
        foreach ($this->componentCompilers[$node->componentPrefix] as $pattern => $callback) {
            if (! Str::is($pattern, $node->tagName)) {
                continue;
            }

            return $callback->call(
                $this,
                $node,
                $this->compileNodes($node->childNodes ?? [])
            );
        }

        return false;
    }
}
