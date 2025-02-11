<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\Dagger\Support\Utils;

trait CompilesCompilerAttributes
{
    protected function compileForAttribute(array $expression, string $compiledOutput): string
    {
        $loopExpression = $expression[0] ?? '';
        $callback = trim($expression[1] ?? '');
        $loopParts = explode('.', $loopExpression);
        $spreadPropValues = false;
        $injectItem = false;

        $callbackData = '';

        if (! isset($loopParts[1])) {
            $spreadPropValues = true;
            $loopParts[1] = '__value';
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

        if ($callback != '') {
            $callbackStub = <<<'PHP'
collect($varName)->mapWithKeys($callback)->all()
PHP;

            $callbackData = Str::swap([
                '$varName' => $variable,
                '$callback' => $callback,
            ], $callbackStub);
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
foreach ($varName as $__key => $__tmp) {
    if (! is_string($__key)) { continue; }
    if (isset($compiledPropNames[$__key])) {
        $componentDataVar[$__key] = $__tmp;
    }
}
unset($__tmp, $__key);
PHP;

            $this->activeComponent->injectedProps[] = str_replace('varName', $varName, $injection);
        }

        $stub = <<<'PHP'
<?php if (isset($varName)) { $__originalVarNameVarSuffix = $varName; }
foreach ($data as $varName) :?>#compiled#<?php endforeach;
if (isset($__originalVarNameVarSuffix)) { $varName = $__originalVarNameVarSuffix; unset($__originalVarNameVarSuffix); }
?>
PHP;

        $temp = Str::swap([
            'VarSuffix' => Utils::makeRandomString(),
            'VarName' => mb_substr($aliasValue, 1),
            '$varName' => $aliasValue,
            '$data' => ($callbackData != '') ? $callbackData : $variable,
            '#compiled#' => $compiledOutput,
        ], $stub);

        return $temp;
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

        return $compiledOutput;
    }
}
