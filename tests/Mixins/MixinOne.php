<?php

namespace Stillat\Dagger\Tests\Mixins;

class MixinOne
{
    public function data(): array
    {
        return [
            'valueOne' => 'Value from mixin one',
            'valueThree' => 'Value three from mixin one',
        ];
    }
}
