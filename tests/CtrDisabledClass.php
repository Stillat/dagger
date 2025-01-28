<?php

namespace Stillat\Dagger\Tests;

use Stillat\Dagger\Ctr\DisableCtr;
use Stillat\Dagger\Ctr\EnableCtr;

#[DisableCtr]
class CtrDisabledClass
{
    public static function methodOne(): string
    {
        return 'Hello, world.';
    }

    #[EnableCtr]
    public static function methodTwo(): string
    {
        return 'Hello, world.';
    }
}
