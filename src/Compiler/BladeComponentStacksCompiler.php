<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use ReflectionMethod;
use ReflectionProperty;
use Stillat\Dagger\Support\Utils;

class BladeComponentStacksCompiler
{
    protected ReflectionProperty $componentHashStackProxy;

    protected ReflectionMethod $compileComponentProxy;

    protected ReflectionMethod $compileEndComponentProxy;

    protected BladeCompiler $compiler;

    public function __construct(BladeCompiler $bladeCompiler)
    {
        $this->compiler = $bladeCompiler;

        $compilerReflection = new \ReflectionClass(BladeCompiler::class);
        $this->componentHashStackProxy = $compilerReflection->getProperty('componentHashStack');
        $this->componentHashStackProxy->setAccessible(true);
        $this->compileComponentProxy = new ReflectionMethod(BladeCompiler::class, 'compileComponent');
        $this->compileEndComponentProxy = new ReflectionMethod(BladeCompiler::class, 'compileEndComponent');
    }

    protected function popComponentHash()
    {
        $currentValue = $this->componentHashStackProxy->getValue();

        $hash = array_pop($currentValue);

        $this->componentHashStackProxy->setValue(null, $currentValue);

        return $hash;
    }

    public function compileComponent($expression)
    {
        return implode(PHP_EOL, [
            $this->compileComponentProxy->invoke($this->compiler, $expression),
            PHP_EOL,
            '<?php if (isset($component)) { \Stillat\Dagger\Facades\ComponentEnv::pushRaw($component); } ?>',
        ]);
    }

    public function compileEndComponentClass($expression)
    {
        $hash = $this->popComponentHash();

        $componentRenderedTemplate = <<<'PHP'
<?php $varName = true; ?>
PHP;

        $checkRenderedTemplate = <<<'PHP'
<?php if (isset($varName) && $varName === true) { \Stillat\Dagger\Facades\ComponentEnv::pop(null); unset($varName); } ?>
PHP;

        $varName = '__didComponentRender'.Utils::makeRandomString();
        $componentRendered = Str::swap(['varName' => $varName], $componentRenderedTemplate);
        $checkRendered = Str::swap(['varName' => $varName], $checkRenderedTemplate);

        $bladeEndComponentClass = $this->compileEndComponentProxy->invoke($this->compiler, $expression)."\n".implode("\n", [
            '<?php endif; ?>',
            '<?php if (isset($__attributesOriginal'.$hash.')): ?>',
            '<?php $attributes = $__attributesOriginal'.$hash.'; ?>',
            '<?php unset($__attributesOriginal'.$hash.'); ?>',
            '<?php endif; ?>',
            '<?php if (isset($__componentOriginal'.$hash.')): ?>',
            '<?php $component = $__componentOriginal'.$hash.'; ?>',
            '<?php unset($__componentOriginal'.$hash.'); ?>',
            '<?php endif; ?>',
        ]);

        return implode(PHP_EOL, [
            $componentRendered,
            $bladeEndComponentClass,
            $checkRendered,
        ]);
    }
}
