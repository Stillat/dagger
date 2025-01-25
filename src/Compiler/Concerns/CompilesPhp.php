<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Use_;
use Stillat\Dagger\Support\Utils;
use Symfony\Component\VarExporter\VarExporter;

trait CompilesPhp
{
    protected function compilePhpArray(array $values): string
    {
        return VarExporter::export($values);
    }

    protected function compileBladePhpBlocks(string $template): string
    {
        return preg_replace_callback('/(?<!@)@php(.*?)@endphp/s', function ($matches) {
            return "<?php{$matches[1]}?>";
        }, $template);
    }

    protected function compileExtractPropsFromParams(): string
    {
        if (count($this->activeComponent->getPropNames()) === 0) {
            return '';
        }

        return '$__componentData = array_intersect_key($__componentData, $compiledPropNames);';
    }

    protected function compileDefaultProps(): string
    {
        $varInitializers = [];
        $componentDataVar = $this->activeComponent->componentDataVar();

        $initializerTemplate = <<<'PHP'
if (! isset($componentDataVar['$varName'])) {
    $componentDataVar['$varName'] = $default;
}
PHP;

        $initializerTemplate = Str::squish($initializerTemplate);

        foreach ($this->activeComponent->getPropDefaults() as $varName => $default) {
            if (in_array($varName, $this->activeComponent->compiledComponentAttributes)) {
                continue;
            }

            $varInitializers[] = Str::swap([
                '$varName' => $varName,
                '$default' => $default,
                'componentDataVar' => $componentDataVar,
            ], $initializerTemplate);
        }

        return implode($this->newlineStyle, $varInitializers);
    }

    protected function compileComponentMixins(): string
    {
        $mixinArray = $this->activeComponent->getMixins();

        if (! $mixinArray) {
            return '';
        }

        $mixins = <<<'PHP'
$component->mixin($mixins);
PHP;

        return Str::swap([
            '$component' => '$'.$this->activeComponent->getVariableName(),
            '$mixins' => $mixinArray,
        ], $mixins);
    }

    protected function compilePropValidation(): string
    {
        if (count($this->activeComponent->getPropValidationRules()) == 0) {
            return '';
        }
        $componentVarName = $this->activeComponent->getVariableName();

        $validator = <<<'PHP'
$__componentValidator = \Illuminate\Support\Facades\Validator::make(
    $componentVarName->data->toArray(),
    [$rules],
    \Stillat\Dagger\Translation\Messages::getValidationMessages($messages)
);

if ($__componentValidator->fails()) {
    throw \Stillat\Dagger\Facades\SourceMapper::makeCompilerException(
        implode(' ', $__componentValidator->messages()->all()),
        '#lineNo#',
        '#path#',
    );
}

unset($__componentValidator);
PHP;

        // Build up our rules.
        $ruleParts = [];

        foreach ($this->activeComponent->getPropValidationRules() as $prop => $rule) {
            $ruleParts[] = "'{$prop}' => {$rule}";
        }

        return Str::swap([
            'componentVarName' => $componentVarName,
            '$rules' => implode(',', $ruleParts),
            "'#lineNo#'" => $this->activeComponent->node->position?->startLine ?? 1,
            '$messages' => $this->activeComponent->getValidationMessages(),
            '#path#' => $this->activeComponent->viewPath,
        ], $validator);
    }

    protected function compileAware(): string
    {
        $varInitializers = [];
        $componentDataVar = $this->activeComponent->componentDataVar();

        $defaultVars = $this->activeComponent->getPropDefaults();
        $awareVars = $this->activeComponent->getAwareVariablesAndDefaults();

        $awareInitializerTemplate = <<<'PHP'
if (! isset($componentDataVar['$varName'])) { 
    if ($__awareResolveVar = $__env->getConsumableComponentData('$varName', $defaultValue)) {
        if ($__awareResolveVar != $checkVal) {
            $componentDataVar['$varName'] = $__awareResolveVar;
        }
    }
}
PHP;

        $awareInitializerTemplate = Str::squish($awareInitializerTemplate);

        foreach ($awareVars as $key => $defaultValue) {
            $varName = $key;
            $awareResolveCheckValue = "'__fallbackVar".Utils::makeRandomString()."'";
            $callDefault = $awareResolveCheckValue;

            if (! is_string($key)) {
                $varName = $defaultValue;

                if (isset($defaultVars[$key])) {
                    $callDefault = $defaultVars[$key];
                }
            } else {
                $callDefault = $defaultValue;
            }

            $varInitializers[] = Str::swap([
                '$varName' => $varName,
                '$defaultValue' => $callDefault,
                'componentDataVar' => $componentDataVar,
                '$checkVal' => $awareResolveCheckValue,
            ], $awareInitializerTemplate);
        }

        return implode($this->newlineStyle, $varInitializers);
    }

    protected function printPhpAst($ast): string
    {
        if (! is_array($ast)) {
            $ast = [$ast];
        }

        return $this->printer->prettyPrint($ast);
    }

    protected function printPhpFile(array $ast): string
    {
        $filteredAst = [];
        $containsNonInlineHtml = false;

        foreach ($ast as $node) {
            if ($node instanceof Use_ || $node instanceof GroupUse) {
                continue;
            }

            if (! $node instanceof InlineHTML) {
                $containsNonInlineHtml = true;
            }

            $filteredAst[] = $node;
        }

        if ($containsNonInlineHtml) {
            array_unshift($filteredAst, new InlineHTML(''));
            $filteredAst[] = new InlineHTML('');
        }

        $content = $this->printer->prettyPrintFile($filteredAst);

        return $this->trimPhpContent($content);
    }

    protected function trimPhpContent(string $content): string
    {
        if (Str::startsWith($content, "?>\n")) {
            $content = ltrim(mb_substr($content, 3));
        }

        $content = rtrim($content);

        if (Str::endsWith($content, '<?php')) {
            $content = mb_substr($content, 0, -5);
        }

        return $content;
    }
}
