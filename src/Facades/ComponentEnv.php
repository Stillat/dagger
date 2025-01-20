<?php

namespace Stillat\Dagger\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\View\Factory;
use Stillat\Dagger\Runtime\Cache\AttributeCache;
use Stillat\Dagger\Runtime\Cache\ComponentCache;
use Stillat\Dagger\Runtime\Component;
use Stillat\Dagger\Runtime\ComponentEnvironment;

/**
 * @method static void push(Factory $viewFactory, Component $component)
 * @method static Component|null activeComponent()
 * @method static void pushRaw(mixed $component)
 * @method static void pop(?Factory $viewFactory)
 * @method static int depth()
 * @method static Component|null last()
 * @method static AttributeCache cache()
 * @method static ComponentCache componentCache()
 */
class ComponentEnv extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ComponentEnvironment::class;
    }
}
