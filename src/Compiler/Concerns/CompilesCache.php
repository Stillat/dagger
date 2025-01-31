<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\Dagger\Cache\CacheAttributeParser;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Support\Utils;

trait CompilesCache
{
    protected function isCacheParam(ParameterNode $param): bool
    {
        return $param->materializedName === '#cache' || Str::startsWith($param->materializedName, '#cache.');
    }

    protected function applyCacheParam(ComponentState $component, ParameterNode $cacheParam): void
    {
        $cacheString = $cacheParam->materializedName;

        if (Str::startsWith($cacheString, '#')) {
            $cacheString = ltrim($cacheString, '#');
        }

        $cacheProperties = CacheAttributeParser::parseCacheString($cacheString);

        if (is_array($cacheProperties->duration)) {
            $now = now()->clone();
            $expires = $now->clone()
                ->addYears($cacheProperties->duration[0])
                ->addMonths($cacheProperties->duration[1])
                ->addWeeks($cacheProperties->duration[2])
                ->addDays($cacheProperties->duration[3])
                ->addHours($cacheProperties->duration[4])
                ->addMinutes($cacheProperties->duration[5])
                ->addSeconds($cacheProperties->duration[6]);

            $cacheProperties->duration = $now->diffInSeconds($expires);
        }

        // TODO: INteroplated variables test.
        if ($cacheParam->type == ParameterType::DynamicVariable) {
            $cacheProperties->key = $cacheParam->value;
        } else {
            $cacheProperties->key = $cacheParam->valueNode->content;
        }

        $component->cacheProperties = $cacheProperties;
    }

    protected function compileCache(string $compiledComponent): string
    {
        if ($this->activeComponent->cacheProperties->duration === 'flexible') {
            $cacheStub = <<<'PHP'
<?php
$__cacheKeyVarSuffix = '#key#';
$__cacheTmpVarsVarSuffix = get_defined_vars();

echo cache()->store('#store#')->flexible($__cacheKeyVarSuffix, [$fresh, $stale], function () use ($__cacheTmpVarsVarSuffix) {
extract($__cacheTmpVarsVarSuffix);
ob_start();
?>#compiled#<?php
    return ob_get_clean();
});
unset($__cacheKeyVarSuffix, $__cacheTmpVars);
?>
PHP;

            $cacheStub = Str::swap([
                '$fresh' => $this->activeComponent->cacheProperties->args[0],
                '$stale' => $this->activeComponent->cacheProperties->args[1],
            ], $cacheStub);

        } else {
            $cacheStub = <<<'PHP'
<?php
$__cacheKeyVarSuffix = '#key#';

if (cache()->store('#store#')->has($__cacheKeyVarSuffix)) {
    echo cache()->store('#store#')->get($__cacheKeyVarSuffix);
    unset($__cacheKeyVarSuffix);
} else { ob_start();
?>#compiled#<?php
    $__cacheResultVarSuffix = ob_get_clean();
    #cacheMethod#
    echo $__cacheResultVarSuffix;
    unset($__cacheKeyVarSuffix, $__cacheKeyVarSuffix);
}
?>
PHP;

            if ($this->activeComponent->cacheProperties->duration === 'forever') {
                $cacheMethod = <<<'PHP'
cache()->store('#store#')->forever($__cacheKeyVarSuffix, $__cacheResultVarSuffix);
PHP;
            } else {
                $cacheMethod = <<<'PHP'
cache()->store('#store#')->put($__cacheKeyVarSuffix, $__cacheResultVarSuffix, '#ttl#');
PHP;

                $cacheMethod = Str::swap([
                    "'#ttl#'" => $this->activeComponent->cacheProperties->duration,
                ], $cacheMethod);
            }

            $cacheStub = Str::swap([
                '#cacheMethod#' => $cacheMethod,
            ], $cacheStub);
        }

        return Str::swap([
            'VarSuffix' => Utils::makeRandomString(),
            '#store#' => $this->activeComponent->cacheProperties->store,
            "'#key#'" => $this->activeComponent->cacheProperties->key,
            '#compiled#' => $compiledComponent,
        ], $cacheStub);
    }
}
