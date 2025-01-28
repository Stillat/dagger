<?php

namespace Stillat\Dagger\Tests;

use Stillat\Dagger\Ctr\DisableCtr;
use Stillat\Dagger\Ctr\EnableCtr;

#[EnableCtr]
class CtrEnabledClass
{
    public static function methodOne(): string
    {
        return 'Hello, world.';
    }

    #[DisableCtr]
    public static function methodTwo(): string
    {
        return 'Hello, world.';
    }
}
