<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Str;
use Stillat\Dagger\Support\Utils;

class BladeComponentStacksCompiler
{
    /**
     * Injects logic into Blade's component output to
     * push components to a custom component stack.
     */
    public function compile(string $template): string
    {
        $nlStyle = Utils::getNewlineStyle($template);
        $lines = explode($nlStyle, $template);

        $componentRenderedTemplate = <<<'PHP'
<?php $varName = true; ?>
PHP;

        $checkRenderedTemplate = <<<'PHP'
<?php if (isset($varName) && $varName === true) { \Stillat\Dagger\Facades\ComponentEnv::pop(null); unset($varName); } ?>
PHP;

        $transformedLines = collect($lines)->map(function ($line) use (
            $componentRenderedTemplate,
            $checkRenderedTemplate
        ) {
            $trimmedLine = trim($line);

            if (Str::startsWith($trimmedLine, '##BEGIN-COMPONENT-CLASS##')) {
                return implode(PHP_EOL, [
                    $line,
                    '<?php if (isset($component)) { \Stillat\Dagger\Facades\ComponentEnv::pushRaw($component); } ?>',
                ]);
            }

            if (str_ends_with($trimmedLine, '##END-COMPONENT-CLASS##')) {
                $varName = '__didComponentRender'.Utils::makeRandomString();
                $componentRendered = Str::swap(['varName' => $varName], $componentRenderedTemplate);
                $checkRendered = Str::swap(['varName' => $varName], $checkRenderedTemplate);

                return implode(PHP_EOL, [
                    $componentRendered,
                    $line,
                    $checkRendered,
                ]);
            }

            return $line;
        });

        return $transformedLines->implode($nlStyle);
    }
}
