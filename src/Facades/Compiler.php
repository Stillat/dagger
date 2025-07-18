<?php

namespace Stillat\Dagger\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Stillat\Dagger\Compiler\CompilerOptions;
use Stillat\Dagger\Compiler\TemplateCompiler;

/**
 * @method static void addComponentViewPath(string $componentPrefix, string $path)
 * @method static void addComponentPath(string $namespace, string $path)
 * @method static void registerComponentPath(string $componentPrefix, string $path, ?string $namespace = null)
 * @method static string compile(string $template)
 * @method static TemplateCompiler compileComponent(string $component, Closure $callback)
 * @method static TemplateCompiler compileComponentWithPrefix(string $prefix, string $component, Closure $callback)
 * @method static string compileWithLineNumbers(string $template)
 * @method static string getDynamicComponentPath(string $proxyName, string|null $componentName = null)
 * @method static bool compiledDynamicComponentExists(string $proxyName, string $componentName)
 * @method static void compileDynamicComponent(array $proxyDetails, string $componentName)
 * @method static CompilerOptions getOptions()
 * @method static string resolveBlocks(string $value)
 * @method static void cleanup()
 * @method static array getComponentBlocks()
 */
class Compiler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TemplateCompiler::class;
    }
}
