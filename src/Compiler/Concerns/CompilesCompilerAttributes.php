<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Compiler\CompilerServices\LoopVariablesExtractor;
use Stillat\Dagger\Support\Utils;

trait CompilesCompilerAttributes
{
    protected function compileForAttribute(string|array $expression, string $compiledOutput): string
    {
        if (is_string($expression)) {
            $loopVarExtractor = new LoopVariablesExtractor;
            $extracted = $loopVarExtractor->extractDetails($expression);
            $loopParts = [$extracted->variable, $extracted->alias];
        } else {
            $loopExpression = $expression[0] ?? '';
            $loopParts = explode('.', $loopExpression);
        }

        $spreadPropValues = false;
        $injectItem = false;

        if (! isset($loopParts[1])) {
            $spreadPropValues = true;
            $loopParts[1] = '__value';
        }

        if (is_string($expression) && str_starts_with($loopParts[1], '...')) {
            $spreadPropValues = true;
            $loopParts[1] = mb_substr($loopParts[1], 4);
        }

        $variable = $loopParts[0];
        $aliasValue = $loopParts[1];

        if (! Str::startsWith($variable, '$')) {
            $variable = '$'.$variable;
        } else {
            $spreadPropValues = true;
        }

        if (! Str::startsWith($aliasValue, '$')) {
            $aliasValue = '$'.$aliasValue;
        } else {
            $injectItem = true;
        }

        if ($injectItem) {
            $injectName = ltrim($loopParts[1], '$');
            $this->activeComponent->injectedProps[] = str_replace(
                'varName', $injectName, "\$componentDataVar['varName'] = \$varName;"
            );
        }

        if ($spreadPropValues) {
            $varName = mb_substr($aliasValue, 1);

            $injection = <<<'PHP'
foreach ($varName as $__daggerForKey => $__daggerForTmpValue) {
    if (! is_string($__daggerForKey)) { continue; }
    if (isset($compiledPropNames[$__daggerForKey])) {
        $componentDataVar[$__daggerForKey] = $__daggerForTmpValue;
    }
}
unset($__daggerForTmpValue, $__daggerForKey);
PHP;

            $this->activeComponent->injectedProps[] = str_replace('varName', $varName, $injection);
        }

        $stub = <<<'PHP'
<?php if (isset($varName)) { $__originalVarNameVarSuffix = $varName; }
foreach ($data as $varName) :?>#compiled#<?php endforeach;
if (isset($__originalVarNameVarSuffix)) { $varName = $__originalVarNameVarSuffix; unset($__originalVarNameVarSuffix); }
?>
PHP;

        return Str::swap([
            'VarSuffix' => Utils::makeRandomString(),
            'VarName' => mb_substr($aliasValue, 1),
            '$varName' => $aliasValue,
            '$data' => $variable,
            '#compiled#' => $compiledOutput,
        ], $stub);
    }

    protected function compileWhenAttribute(string $expression, string $compiledOutput): string
    {
        return <<<PHP
<?php if ($expression): ?>$compiledOutput<?php endif; ?>
PHP;
    }

    protected function compileCompilerAttributes(string $compiledOutput): string
    {
        if (empty($this->activeComponent->compilerAttributes)) {
            return $compiledOutput;
        }

        if (isset($this->activeComponent->compilerAttributes['for'])) {
            $compiledOutput = $this->compileForAttribute(
                $this->activeComponent->compilerAttributes['for'],
                $compiledOutput
            );
        }

        if (isset($this->activeComponent->compilerAttributes['when'])) {
            $compiledOutput = $this->compileWhenAttribute(
                $this->activeComponent->compilerAttributes['when'],
                $compiledOutput,
            );
        }

        return $compiledOutput;
    }
}
