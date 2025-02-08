<?php

namespace Stillat\Dagger\Tests;

use Stillat\Dagger\Compiler\DisableOptimization;
use Stillat\Dagger\Compiler\EnableOptimization;

#[EnableOptimization]
class CtrEnabledClass
{
    public static function methodOne(): string
    {
        return 'Hello, world.';
    }

    #[DisableOptimization]
    public static function methodTwo(): string
    {
        return 'Hello, world.';
    }
}
