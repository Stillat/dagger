<?php

namespace Stillat\Dagger\Runtime\Cache;

use Illuminate\View\ComponentAttributeBag;

class AttributeCache
{
    protected array $items = [];

    public static function getAttributeCacheKey(array $params): ?string
    {
        $cacheKey = '';

        foreach ($params as $key => $value) {
            if (is_object($value)) {
                if ($value instanceof ComponentAttributeBag) {
                    $cacheKey .= $key.'|'.$value->toHtml();

                    continue;
                }

                return null;
            }

            $cacheKey .= $key.'|'.$value;
        }

        return md5($cacheKey);
    }

    public function has(?string $key): bool
    {
        if ($key === null) {
            return false;
        }

        return array_key_exists($key, $this->items);
    }

    public function put(?string $key, string $content): void
    {
        if (! $key) {
            return;
        }

        $this->items[$key] = $content;
    }

    public function get(string $key): string
    {
        return $this->items[$key];
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }
}
