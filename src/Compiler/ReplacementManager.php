<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Str;
use Stillat\Dagger\Support\Utils;

class ReplacementManager
{
    protected array $regions = [];

    public function getReplacement(ComponentState $component, string $scope): string
    {
        if (! array_key_exists($component->getCachePrefix(), $this->regions)) {
            $this->regions[$component->getCachePrefix()] = [];
        }

        if (array_key_exists($scope, $this->regions[$component->getCachePrefix()])) {
            return $this->regions[$component->getCachePrefix()][$scope];
        }

        return $this->regions[$component->getCachePrefix()][$scope] = '__REPL::'.Str::upper($scope).Utils::makeRandomString();
    }

    public function getPlaceholders(ComponentState $component): array
    {
        if (! array_key_exists($component->getCachePrefix(), $this->regions)) {
            return [];
        }

        return array_values($this->regions[$component->getCachePrefix()]);
    }

    public function clear(): void
    {
        $this->regions = [];
    }
}
