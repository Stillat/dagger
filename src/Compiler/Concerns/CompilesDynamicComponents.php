<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\Dagger\Compiler\ParameterFactory;
use Stillat\Dagger\Facades\SourceMapper;
use Stillat\Dagger\Support\Utils;

trait CompilesDynamicComponents
{
    public static function getDynamicComponentContent(string $result, string $dynamicName): string
    {
        return Str::after($result, '[---'.$dynamicName.'---]');
    }

    protected function printComponent(ComponentNode $component): string
    {
        $output = '<'.$component->componentPrefix.'-'.$component->name.' ';

        if (! empty($component->parameters)) {
            $output .= collect($component->parameters)
                ->map(fn (ParameterNode $param) => $param->content)
                ->join(' ');
        }

        if ($component->isSelfClosing) {
            $output .= ' />';

            return $output;
        } else {
            $output .= '>';
        }

        $output .= $component->innerDocumentContent;

        $output .= '</'.$component->componentPrefix.'-'.$component->name.'>';

        return $output;
    }

    public function getDynamicComponentPath(string $dynamicComponentName, ?string $componentName = null): string
    {
        if ($componentName) {
            $dynamicComponentName .= '::'.$componentName;
        }

        $dynamicComponentName = sha1($dynamicComponentName);

        return $this->options->viewCachePath.'_dynamicComponent'.$dynamicComponentName.'.php';
    }

    public function compiledDynamicComponentExists(string $dynamicComponentName, string $componentName): bool
    {
        return file_exists($this->getDynamicComponentPath($dynamicComponentName, $componentName));
    }

    public function compileDynamicComponent(array $componentDetails, string $componentName): ?string
    {
        $dynamicComponentName = $componentDetails['component_name'];
        $template = base64_decode($componentDetails['component'] ?? '');
        $dynamicComponentPath = $this->getDynamicComponentPath($dynamicComponentName, $componentName);

        if (! Str::contains($template, $dynamicComponentName)) {
            return null;
        }

        $template = Str::swap([
            $dynamicComponentName => $componentName,
        ], $template);

        file_put_contents($dynamicComponentPath, $this->compiler->compileString($template));

        return $dynamicComponentPath;
    }

    protected function compileCircularComponent(ComponentNode $node, string $currentViewPath): string
    {
        $circularComponent = clone $node;
        $circularComponent->name = 'delegate-component';
        $circularComponent->parameters[] = ParameterFactory::parameterFromText('component="'.$node->tagName.'"');

        return $this->compileDynamicComponentScaffolding(
            $circularComponent,
            $currentViewPath,
            $this->getComponentHash($node)
        );
    }

    protected function compileDynamicComponentScaffolding(ComponentNode $component, string $viewPath, ?string $dynamicComponentName = null): string
    {
        if (! $dynamicComponentName) {
            $dynamicComponentName = Utils::makeRandomString();
        }

        $dynamicComponent = clone $component;
        $dynamicComponent->parameters = collect($dynamicComponent->parameters)
            ->filter(fn (ParameterNode $param) => $param->materializedName != 'component')
            ->all();
        $dynamicComponent->parameterCount = count($dynamicComponent->parameters);
        $dynamicComponent->tagName = $dynamicComponent->name = $dynamicComponentName;

        $componentVar = collect($component->parameters)
            ->first(fn (ParameterNode $param) => $param->materializedName == 'component');

        if (! $componentVar) {
            throw SourceMapper::makeComponentCompilerException(
                $component,
                'Missing [component] property. Dynamic components must specify an explicit [component] property.',
                $viewPath
            );
        }

        $contentDelimiter = '[DYNAMIC::COMPONENT::CONTENT'.md5($dynamicComponentName).']';

        // Ensure line numbers remain consistent.
        $dynamicTemplate = str_repeat($this->newlineStyle, ($component->position?->startLine ?? 1) - 1);
        $dynamicTemplate .= $contentDelimiter;
        $dynamicTemplate .= $this->printComponent($dynamicComponent);

        $componentDetails = [
            'component' => base64_encode($dynamicTemplate),
            'component_name' => $dynamicComponentName,
            'content_delimiter' => base64_encode($contentDelimiter),
            'component_stack' => base64_encode(serialize($this->componentStack)),
            // If the manifest file is deleted between dynamic component renders,
            // we can end up with an invalid manifest file making invalidation
            // not work correctly. To avoid this, we will save the current
            // manifest state with the dynamic component output as well.
            'manifest' => base64_encode(json_encode([
                'paths' => $this->manifest->getRootStoragePaths(),
                'state' => $this->manifest->getTracked(),
            ])),
        ];

        $componentTemplate = <<<'PHP'
<?php
$__compiledDynamicComponentName = $componentVar;

if (! $__compiledDynamicComponentName) {
    throw \Stillat\Dagger\Facades\SourceMapper::makeCompilerException('Invalid dynamic component name. Expecting non-empty string, received ['.gettype($__compiledDynamicComponentName).'].', '#lineNo#', '#viewPath#');
} elseif (\Stillat\Dagger\Facades\Compiler::compiledDynamicComponentExists('DynamicComponentName', $__compiledDynamicComponentName)) {
    try {
        ob_start();
        include \Stillat\Dagger\Facades\Compiler::getDynamicComponentPath('DynamicComponentName', $__compiledDynamicComponentName);
        echo \Illuminate\Support\Str::after(ob_get_clean(), '#contentDelimiter#');
    } catch (\Stillat\Dagger\Exceptions\ComponentException $exception) {
        throw \Stillat\Dagger\Facades\SourceMapper::convertFileDynamicException($exception, base64_decode('#template#'), $__compiledDynamicComponentName, 'DynamicComponentName');
    }
} else {
    if ($compiledPath = \Stillat\Dagger\Facades\Compiler::compileDynamicComponent($componentDetails, $__compiledDynamicComponentName)) {
        try {
            ob_start();
            include $compiledPath;
            echo \Illuminate\Support\Str::after(ob_get_clean(), '#contentDelimiter#');
        } catch (\Stillat\Dagger\Exceptions\ComponentException $exception) {
            throw \Stillat\Dagger\Facades\SourceMapper::convertFileDynamicException($exception, base64_decode('#template#'), $__compiledDynamicComponentName, 'DynamicComponentName');
        } 
    }
}
unset($__compiledDynamicComponentName);
?>
PHP;

        return Str::swap([
            'DynamicComponentName' => $dynamicComponentName,
            '$componentVar' => $this->attributeCompiler->compileValue($componentVar),
            '$componentDetails' => $this->compilePhpArray($componentDetails),
            '#template#' => base64_encode($dynamicTemplate),
            "'#lineNo#'" => $componentVar->position?->startLine ?? 1,
            '#viewPath#' => $viewPath,
            '#contentDelimiter#' => $contentDelimiter,
        ], $componentTemplate);
    }
}
