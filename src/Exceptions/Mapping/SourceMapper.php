<?php

namespace Stillat\Dagger\Exceptions\Mapping;

use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpParser\Error;
use ReflectionProperty;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Exceptions\ComponentException;
use Stillat\Dagger\Facades\Compiler;
use Throwable;

class SourceMapper extends BladeMapper
{
    protected function getLineNumber(string $template, int $compiledLineNumber): int
    {
        return $this->findClosestLineNumberMapping(
            Compiler::compileWithLineNumbers($template),
            $compiledLineNumber
        );
    }

    protected function addPathLine(string $message, string $path): string
    {
        if ($path === '') {
            return $message;
        }

        return "{$message}\n(View: {$path})";
    }

    public function convertFileDynamicException(ComponentException $exception, string $template, string $componentName, string $dynamicReplacement)
    {
        $template = str_replace($dynamicReplacement, $componentName, $template);
        $compiledLine = $exception->originalLineNumber ?? $exception->getLine();
        $line = $this->getLineNumber($template, $compiledLine);

        $newException = new ComponentException(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getSeverity(),
            $exception->getFile(),
            $line,
            null
        );

        $this->setTrace($newException, $this->cleanTrace($exception->getTrace()));

        return $newException;
    }

    public function makeCompilerException(string $message, int $line, string $path): ComponentException
    {
        $newException = new ComponentException(
            $this->addPathLine($message, $path),
            filename: $path,
            line: $line
        );

        $this->setTrace($newException, $this->cleanTrace(debug_backtrace()));

        return $newException;
    }

    public function makeComponentCompilerException(ComponentNode $componentNode, string $message, string $path): ComponentException
    {
        return $this->makeCompilerException($message, $componentNode->position?->startLine ?? 1, $path);
    }

    public function convertParserError(Error $error, string $template, string $path, int $lineOffset = 0): ComponentException
    {
        $lineNumber = $this->getLineNumber($template, $error->getLine());
        $message = $this->addPathLine($error->getMessage(), $path);

        if ($lineOffset > 0) {
            $lineOffset -= 1;

            $lineNumber += $lineOffset;

            if (Str::contains($message, 'on line ')) {
                $message = Str::before($message, 'on line ');
                $message .= 'on line '.$lineNumber;
            }
        }

        $newException = new ComponentException($message, $error->getCode(), 1, $path, $lineNumber, null);
        $newException->originalLineNumber = $error->getLine();

        $this->setTrace($newException, $this->cleanTrace($error->getTrace()));

        return $newException;
    }

    public function convertException(Throwable $exception, string $sourcePath, string $rootPath, array $componentTrace): ComponentException
    {
        $lineNumber = $this->getLineNumber(file_get_contents($rootPath), $exception->getLine());

        $newException = new ComponentException($exception->getMessage(), $exception->getCode(), 1, $sourcePath, $lineNumber, null);
        $newException->originalLineNumber = $exception->getLine();

        $trace = $this->rewriteTrace($exception->getTrace());

        foreach ($componentTrace as $item) {
            array_unshift($trace, [
                'file' => $item['file'],
                'line' => $item['line'],
            ]);
        }

        $this->setTrace($newException, $this->cleanTrace($trace));

        return $newException;
    }

    protected function cleanTrace(array $trace): array
    {
        return collect($trace)->filter(function ($frame) {
            if (isset($frame['class']) && Str::contains($frame['class'], 'Stillat\\Dagger\\Exceptions\\')) {
                return false;
            }

            return true;
        })->values()->all();
    }

    protected function setTrace(Throwable $exception, array $trace): void
    {
        $traceProperty = new ReflectionProperty('Exception', 'trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($exception, $trace);
    }

    protected function rewriteTrace(array $trace): array
    {
        return collect($trace)
            ->map(function ($frame) {
                if ($originalPath = $this->findCompiledView((string) Arr::get($frame, 'file', ''))) {
                    $frame['file'] = $originalPath;
                    $frame['line'] = $this->detectLineNumber($frame['file'], $frame['line']);
                }

                return $frame;
            })->toArray();
    }

    public function addBladeLineNumbers(string $value): string
    {
        try {
            $value = $this->addEchoLineNumbers($value);
            $value = $this->addStatementLineNumbers($value);

            return $this->addBladeComponentLineNumbers($value);
        } catch (Throwable $e) {
            report($e);

            return $value;
        }
    }

    protected function insertLineNumberAtPosition(int $position, string $value)
    {
        $before = mb_substr($value, 0, $position);

        $lineNumber = count(explode("\n", $before));

        return mb_substr($value, 0, $position)."/** |---LINE:{$lineNumber}---| */".mb_substr($value, $position);
    }
}
