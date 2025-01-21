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
        $newLines = [];

        $componentRenderedTemplate = <<<'PHP'
<?php $varName = true; ?>
PHP;

        $checkRenderedTemplate = <<<'PHP'
<?php if (isset($varName) && $varName === true) { \Stillat\Dagger\Facades\ComponentEnv::pop(null); unset($varName); } ?>
PHP;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);

            if (Str::startsWith($trimmedLine, '##BEGIN-COMPONENT-CLASS##')) {
                while (! str_starts_with($trimmedLine, '<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>')) {
                    $newLines[] = $line;
                    $i++;

                    $line = $lines[$i];
                    $trimmedLine = trim($line);
                }
                $newLines[] = '<?php if (isset($component)) { \Stillat\Dagger\Facades\ComponentEnv::pushRaw($component); } ?>';
                $newLines[] = $line;

                continue;
            }

            if (str_ends_with($trimmedLine, '##END-COMPONENT-CLASS##')) {
                $varName = '__didComponentRender'.Utils::makeRandomString();
                $componentRendered = Str::swap(['varName' => $varName], $componentRenderedTemplate);
                $checkRendered = Str::swap(['varName' => $varName], $checkRenderedTemplate);

                $newLines[] = implode(PHP_EOL, [
                    $componentRendered,
                    $line,
                    $checkRendered,
                ]);

                continue;
            }

            $newLines[] = $line;
        }

        return implode($nlStyle, $newLines);
    }
}
