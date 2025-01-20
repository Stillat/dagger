<?php

namespace Stillat\Dagger\Compiler\ComponentStages;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use Stillat\Dagger\Compiler\ComponentCompiler;
use Stillat\Dagger\Support\Utils;

class ExtractsRenderCalls extends AbstractStage
{
    protected ComponentCompiler $compiler;

    public function __construct(ComponentCompiler $compiler)
    {
        parent::__construct();

        $this->compiler = $compiler;
    }

    public function handle($ast, Closure $next)
    {
        $placeholders = [];
        $this->traverser->addVisitor(new class($placeholders) extends NodeVisitorAbstract
        {
            private array $placeholders;

            public function __construct(&$placeholders)
            {
                $this->placeholders = &$placeholders;
            }

            public function enterNode(Node $node)
            {
                if (! $node instanceof FuncCall) {
                    return null;
                }

                if (! $this->containsRenderCall($node)) {
                    return null;
                }

                $placeholder = '__RENDER::'.Utils::makeRandomString();

                $this->placeholders[] = [
                    'placeholder' => $placeholder,
                    'node' => $node,
                ];

                return new String_($placeholder);
            }

            private function containsRenderCall(FuncCall $node): bool
            {
                if ($node->name instanceof Name && $node->name->toString() === 'Stillat\Dagger\render') {
                    return true;
                }

                foreach ($node->args as $arg) {
                    if (! $arg->value instanceof FuncCall) {
                        continue;
                    }

                    if (! $this->containsRenderCall($arg->value)) {
                        continue;
                    }

                    return true;
                }

                return false;
            }
        });

        $ast = $this->traverser->traverse($ast);

        $this->compiler->setRenders($placeholders);

        return $next($ast);
    }
}
