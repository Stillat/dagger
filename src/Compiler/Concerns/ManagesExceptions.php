<?php

namespace Stillat\Dagger\Compiler\Concerns;

use Illuminate\Support\Str;
use Stillat\Dagger\Compiler\ComponentState;

trait ManagesExceptions
{
    protected function compileExceptions(string $template): string
    {
        return Str::swap([
            "'#componentTrace#'" => $this->compilePhpArray($this->compileComponentTraces()),

        ], $template);
    }

    protected function compileComponentTraces(): array
    {
        $trace = [];

        $isRoot = true;
        $lastPath = '';

        /** @var ComponentState $state */
        foreach ($this->componentStack as $state) {
            $file = $isRoot ? $state->viewPath : $lastPath;

            $trace[] = [
                'file' => realpath($file),
                'line' => $state->node->position?->startLine ?? 1,
            ];

            $isRoot = false;
            $lastPath = $state->componentPath;
        }

        return $trace;
    }
}
