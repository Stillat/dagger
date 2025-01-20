<?php

namespace Stillat\Dagger\Facades;

use Exception;
use Illuminate\Support\Facades\Facade;
use PhpParser\Error;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Exceptions\ComponentException;
use Stillat\Dagger\Exceptions\Mapping\SourceMapper as SourceMapperImpl;

/**
 * @method static ComponentException makeComponentCompilerException(ComponentNode $componentNode, string $message, string $path)
 * @method static ComponentException makeCompilerException(string $message, int $line, string $path)
 * @method static ComponentException convertParserError(Error $error, string $template, string $path, int $lineOffset = 0)
 * @method static ComponentException convertException(Exception $exception, string $sourcePath, string $rootPath, array $componentTrace)
 * @method static ComponentException convertFileDynamicException(ComponentException $exception, string $template, string $componentName, string $dynamicReplacement)
 * @method static string addBladeLineNumbers(string $value)
 */
class SourceMapper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SourceMapperImpl::class;
    }
}
