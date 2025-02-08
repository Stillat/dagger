<?php

namespace Stillat\Dagger\Tests;

use Stillat\Dagger\Compiler\DisableOptimization;
use Stillat\Dagger\Compiler\EnableOptimization;

#[DisableOptimization]
class CtrDisabledClass
{
    public static function methodOne(): string
    {
        return 'Hello, world.';
    }

    #[EnableOptimization]
    public static function methodTwo(): string
    {
        return 'Hello, world.';
    }
}
