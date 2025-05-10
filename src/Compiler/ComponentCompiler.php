<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Pipeline\Pipeline;
use PhpParser\PrettyPrinter;
use PhpParser\PrettyPrinter\Standard;
use Stillat\Dagger\Compiler\ComponentStages\RemoveUseStatements;
use Stillat\Dagger\Compiler\ComponentStages\ResolveNamespaces;
use Stillat\Dagger\Compiler\ComponentStages\RewriteFunctions;
use Stillat\Dagger\Compiler\Concerns\CompilesPhp;
use Stillat\Dagger\Parser\PhpParser;

class ComponentCompiler
{
    use CompilesPhp;

    protected PrettyPrinter $printer;

    public function __construct()
    {
        $this->printer = new Standard;
    }

    public function compile(string $component): string
    {
        $ast = app(Pipeline::class)
            ->send(PhpParser::makeParser()->parse($component))
            ->through([
                ResolveNamespaces::class,
                RemoveUseStatements::class,
                RewriteFunctions::class,
            ])
            ->thenReturn();

        return $this->printPhpFile($ast);
    }
}
