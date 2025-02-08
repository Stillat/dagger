<?php

namespace Stillat\Dagger\Cache;

class CacheProperties
{
    public function __construct(
        public string|array $duration,
        public string $store,
        public array $args = [],
        public string $key = '',
    ) {}
}
