<?php

namespace Stillat\Dagger\Tests\Mixins;

class MixinTwo
{
    public function data(): array
    {
        return [
            'valueOne' => 'Value from mixin two',
            'valueTwo' => 'Value two',
            'valueThree' => 'Value three from mixin two',
        ];
    }
}
