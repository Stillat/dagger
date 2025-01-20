<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use Stillat\Dagger\Compiler\Concerns\CompilesPhp;
use Stillat\Dagger\Parser\PhpParser;

class StaticTemplateCompiler
{
    use CompilesPhp;

    protected Parser $phpParser;

    protected Standard $printer;

    protected array $phpOpeningTokens = [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO];

    public function __construct()
    {
        $this->phpParser = PhpParser::makeParser();
        $this->printer = new Standard;
    }

    public function compile(string $template): string
    {
        return $this->printPhpFile($this->phpParser->parse($template));
    }

    public function isStaticTemplate(string $template, array $placeholders = []): bool
    {
        if (Str::contains($template, $placeholders)) {
            return false;
        }

        foreach (token_get_all(Blade::compileString($template)) as $token) {
            if (is_array($token) && in_array($token[0], $this->phpOpeningTokens, true)) {
                return false;
            }
        }

        return true;
    }

    protected function compileStaticComponentTemplate(ComponentState $component, string $template): string
    {
        if ($component->getTrimOutput()) {
            $template = trim($template);
        }

        return $template;
    }

    public function testTemplate(ComponentState $component, string $template, array $placeholders = []): array
    {
        $compiled = $this->compile($template);
        $isStatic = $this->isStaticTemplate($compiled, $placeholders);

        if ($isStatic) {
            $compiled = $this->compileStaticComponentTemplate($component, $compiled);
        }

        return [
            $isStatic,
            $isStatic ? $compiled : $template,
        ];
    }
}
