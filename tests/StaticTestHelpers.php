<?php

namespace Stillat\Dagger\Tests;

class StaticTestHelpers
{
    protected static $counter = 0;

    public static function counter(): int
    {
        self::$counter += 1;

        return self::$counter;
    }

    public static function resetCounter(): void
    {
        self::$counter = 0;
    }
}
