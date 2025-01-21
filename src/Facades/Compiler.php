<?php

namespace Stillat\Dagger\Facades;

use Illuminate\Support\Facades\Facade;
use Stillat\Dagger\Compiler\CompilerOptions;
use Stillat\Dagger\Compiler\TemplateCompiler;

/**
 * @method static void addComponentViewPath(string $componentPrefix, string $path)
 * @method static void addComponentPath(string $namespace, string $path)
 * @method static void registerComponentPath(string $componentPrefix, string $path, ?string $namespace = NULL)
 * @method static string compile(string $template)
 * @method static string compileWithLineNumbers(string $template)
 * @method static string getDynamicComponentPath(string $proxyName, string|null $componentName = null)
 * @method static bool compiledDynamicComponentExists(string $proxyName, string $componentName)
 * @method static void compileDynamicComponent(array $proxyDetails, string $componentName)
 * @method static CompilerOptions getOptions()
 * @method static cleanup()
 */
class Compiler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TemplateCompiler::class;
    }
}
