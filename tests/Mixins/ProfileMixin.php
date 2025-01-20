<?php

namespace Stillat\Dagger\Tests\Mixins;

class ProfileMixin
{
    public function sayHello(string $name): string
    {
        return "Hello, {$name}.";
    }
}
